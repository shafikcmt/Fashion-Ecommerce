# Architecture

## Application boundaries

app/Services contains the transaction boundaries. Models and migrations hold the shared schema. Blade controllers and Livewire components implement the customer experience. Filament resources call the same services for stock/order operations. Laravel policies enforce authorization on the server, including custom admin actions.

- CartService: persistent guest/customer carts, ownership-scoped access, atomic quantity updates and login merge capped at available stock.
- InventoryService: locked stock adjustments and movement history. Stock/reserved columns are never editable through generic forms.
- CheckoutService: shared quote/placement validation, server prices, shipping eligibility, coupon eligibility and limits, integer totals, reservation and immutable snapshots.
- OrderService: status transitions, expiration/cancellation release, shipment consumption, COD collection, return decisions and approved restocking.
- MediaService: MIME/content/size/dimension validation and reference-safe deletion.
- CatalogCsvService: validated variant import for existing products, all-or-nothing changes, row errors and spreadsheet-safe export.

## Money and checkout

All stored money is integer **poisha**: 100 = BDT 1. Percentage discounts use integer division, rounding down to a poisha. Variant prices are re-read under row locks. Coupon discounts apply to eligible category lines, are capped at their eligible subtotal, and require the bag subtotal to meet the configured minimum.

Customers review exact server totals before placement. Placement validates a fingerprint of reviewed item snapshots and totals; a changed quote requires review again. Submitted client totals are ignored.

Checkout locks the cart, coupon (when present), and variant rows ordered by ID inside a retried database transaction. checkout_key has a unique database constraint. Repeated placement with the same session key returns the same order before reading the now-empty cart. Separate-process PostgreSQL tests exercise contention and retry behavior.

Guest coupon limits use normalized email identity; they are not a verified-person fraud prevention system. Shipping zones contain exact district names or *, and are chosen explicitly by the shopper.

## Inventory and state

Available stock equals stock minus reserved. Pending COD orders reserve inventory for 24 hours. Scheduled expiration rechecks the order under a lock. Confirmation clears the expiry but retains the reservation; confirmed orders require manual shipment or cancellation. Shipment reduces both stock and reserved counts and records a movement.

~~~text
pending → confirmed → shipped → delivered
pending → cancelled
confirmed → cancelled
~~~

Fulfillment and payment statuses remain separate. COD is marked paid only by an authorized admin action after delivery. A return request can be approved/rejected once. Approval alone does not restock: the admin must confirm receipt explicitly. Restocking is one-time. Manual refund recording is a separate action after receipt of a paid return. Returns currently cover the whole order.

Order items preserve name, SKU, size, color, unit price, quantity and line total. Eloquent rejects snapshot modification/deletion; PostgreSQL also enforces this with a trigger. Products/categories/variants referenced by orders are protected by foreign keys; products are archived instead of deleted.

## Identity and security

Laravel sessions, CSRF protection and password broker are used. Public auth and checkout have rate limits; Filament provides login throttling. Registration/profile update cannot assign roles. Admin access requires an administrative role and verified email; model policies also apply to resources and custom actions.

Guest receipts use the guest's server session or expiring signed links. Other customers cannot access orders by guessing UUIDs. Invoice and guest cancellation/return links use short-lived signatures. Signed URLs are bearer access links; protect them as customer data.

Uploads accept JPEG/PNG/WebP only, at most 5 MiB and 6000×6000 pixels. SVG and arbitrary files are rejected. Gallery/variant/content foreign keys prevent deleting referenced media; logo/social image settings are checked too. Editable text is escaped; rich HTML is not supported. Product JSON-LD uses safe JSON encoding.

Audit records store actor, action, record type/ID and changed field names, never raw customer or credential values. Notifications are queued and dispatched after commit. Sessions are encrypted in the supplied configuration. Sensitive responses use Cache-Control: no-store.

Provider interfaces expose initiation, server verification and webhook handling. Their current implementation refuses all operations. No redirect can mark an order paid. Live providers must add verified transaction matching, signed callbacks, unique provider transaction IDs and idempotent callback storage before being enabled.

## Reporting

Collected revenue sums order totals with **current payment_status = paid**, including shipping. Unpaid COD and refunded orders are excluded. Date filters apply to **order creation time** in Asia/Dhaka; this is not an accounting ledger or collection-date report. Best sellers use paid item quantities. Low-stock data is current availability against each variant's threshold.
