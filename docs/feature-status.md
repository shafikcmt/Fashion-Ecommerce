# Feature status

The application implements the five core milestones locally. Online payments are deliberately disabled. Read this together with the validation limits below.

| Milestone | Implemented | Evidence |
| --- | --- | --- |
| 1 · Foundation | Laravel, verified customer accounts, reset tokens, scoped admin roles/policies, safe admin command, CI configuration | PHP auth, ownership and admin authorization tests |
| 2 · Catalog & inventory | Products/categories/collections/brands/tags, gallery/variant images, SKU/options constraints, price validation, draft/published/archive, stock movements, low-stock reporting, CSV import/export | PHP variant, price, stock, CSV failure, upload and admin action tests |
| 3 · Storefront & CMS | Responsive Blade/Livewire storefront, English/Bangla locale, BDT, configurable brand/contact, hero slides, promotions, navigation/footer, text pages, search/filter/sort/pagination, persistent carts, login merge and wishlist | Render/XSS/Unicode/Livewire/cart tests; desktop/mobile catalog and Bangla browser tests |
| 4 · COD commerce | Server quote review and totals, shipping zones, expiry/limits/category coupons, atomic reservations, retry keys, snapshots, separate statuses, confirmation queue, order tracking, invoices and cancellation | PHP totals/limits/retry/status/expiry/ownership tests; desktop/mobile complete COD browser flow |
| 5 · Returns/reporting/SEO | Return request/review/receipt/restock, manual refunds, date-filtered real reports, canonical/OG metadata, sitemap, product JSON-LD, courier assignment and provider interfaces | PHP return/state/receipt tests, admin reports render, sitemap/render tests |

## Locally verified

- Migrations ran on SQLite, including the localization migration.
- PHP suite: 37 passed, 3 PostgreSQL-specific tests skipped (40 total), 184 assertions.
- Four browser scenarios passed across desktop/mobile runs: catalog/Bangla navigation and guest purchase → review → COD receipt → invoice → cancellation.
- Production Vite build passed. Pint formatting passed. Blade and route compilation passed.
- Composer schema is valid. Strict validation warns about exact semver pins, which are intentional to satisfy dependency pinning. Both lockfiles are included.
- npm reported no dependency vulnerabilities during installation/lock refresh.

These counts are recorded from actual local runs. The upload test uses a unique fake disk to avoid repeated-directory reuse failures on Windows.

## CI verification

GitHub Actions run [38119265972](https://github.com/shafikcmt/Fashion-Ecommerce/actions/runs/38119265972) passed on commit `ab855fe`. Its PostgreSQL 17 migrations and full PHP suite, Redis connection check, lint, production build, compiled views/routes, and Chromium browser scenarios all passed. The three PostgreSQL race tests are included in that CI suite.

## Configured but not verified locally

- PostgreSQL runtime checks and three separate-process race tests. This host has no PostgreSQL server or Docker. CI is configured to run them against PostgreSQL 17; local SQLite tests do not prove PostgreSQL concurrency behavior.
- Redis queues/cache/rate-limiter backend. Redis health check correctly reports unavailable here; CI contains a connectivity check. Local fallback used database sessions/cache/queues.
- Docker Compose startup, S3 bucket access, real SMTP delivery, backup recovery and a production deployment. Instructions are provided, not a completed deployment claim.
- GitHub Actions passed remotely; local Docker Compose startup remains unverified.

## Pending credentials

bKash, Nagad and SSLCommerz are **disabled**, with no callback endpoints. Provide official sandbox account access, current merchant integration documentation, callback/public URLs and webhook verification credentials. Implement server transaction verification and callback idempotency before enabling any online method. Redirect/client success must never mark an order paid.

Courier APIs are **planned** behind CourierProvider. Manual courier/tracking entry is implemented.

S3 storage is configured through Laravel/Flysystem. Supply endpoint, bucket, region, scoped access credentials and a public image URL policy. Supply production SMTP credentials and verified sender configuration for email delivery.

## Intentional boundaries / remaining limitations

- Returns cover an entire order, with manual approval, receipt and refund recording; partial-item returns and automated refunds are planned.
- Checkout uses a 64-district dropdown (Bangla names in Bangla mode), an optional upazila/thana/area, phone-first contact (email optional; phone-derived placeholder identity, no confirmation email) and three delivery zones (Inside Dhaka ৳70, rest of Dhaka division ৳100, outside Dhaka ৳130), picked automatically from the district. The `free_delivery_min` setting (whole taka, demo ৳3,000) makes delivery free once the discounted subtotal reaches it. SMS confirmation, upazila-level pricing, cash collection reconciliation, tax accounting and carrier APIs are planned (SMS needs a gateway account).
- Coupon per-customer limits use normalized email, including guest email; verified-person abuse prevention is not implemented.
- Body content is escaped plain text. Rich HTML editing is not supported. Some admin labels and diagnostic messages remain English. Empty Bangla fields fall back to English.
- Low-stock alerts appear in dashboard/reports; scheduled email stock alerts are planned.
- CSV manages variants of existing products, up to 5,000 rows, with all-or-nothing imports. Full product/media CSV creation and background large imports are planned.
- Storefront filters include text, size, color, min/max price and sorting. Faceted counts and advanced full-text search are planned.
- Demo names, photos, stock, sizes, contacts and policy pages are development samples. Replace them with actual store content before launch.
- No external payment, deployment, email delivery or PostgreSQL/Redis production readiness has been claimed without verification.

## Phase 6 additions (not yet run locally: PHP 8.4 was unavailable in the authoring environment)

- Search matches English and Bangla names, fabric, descriptions and category, and expands common spelling variants (shari/sari/saree/শাড়ি, panjabi/punjabi/পাঞ্জাবি, three-piece/থ্রি-পিস, hijab, lungi and others; see `App\Support\SearchTerms`). PostgreSQL uses `ILIKE`.
- Filters: colour and fabric dropdowns built from real data, in-stock-only, price, size and sort.
- Reviews: customers (guests or signed in) submit a 1–5 star review; it stays hidden until a catalog manager approves it under Catalog & inventory → Product reviews. Only approved reviews are shown, averaged on cards and the product page and emitted as `aggregateRating` JSON-LD. A signed-in customer has one review per product (edits return it to pending) and gets a "verified purchase" badge when a delivered order contains the product. A honeypot field and a per-minute limit reduce spam.
- Order tracking: `/track-order` takes the order number and checkout phone and opens the order page through a one-hour signed link; wrong number or phone gives the same generic error.
- Delivery of static assets: nginx serves `/build` with a one-year immutable cache, demo/images for seven days, and gzip.
- Not done: automatic WebP/responsive image generation, a CDN, Bangla admin labels and a Bangla-aware full-text index.
