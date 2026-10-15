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
- Shipping uses exact district names and flat zone charges. Address normalization, cash collection reconciliation, tax accounting and carrier APIs are planned.
- Coupon per-customer limits use normalized email, including guest email; verified-person abuse prevention is not implemented.
- Body content is escaped plain text. Rich HTML editing is not supported. Some admin labels and diagnostic messages remain English. Empty Bangla fields fall back to English.
- Low-stock alerts appear in dashboard/reports; scheduled email stock alerts are planned.
- CSV manages variants of existing products, up to 5,000 rows, with all-or-nothing imports. Full product/media CSV creation and background large imports are planned.
- Storefront filters include text, size, color, min/max price and sorting. Faceted counts and advanced full-text search are planned.
- Demo names, photos, stock, sizes, contacts and policy pages are development samples. Replace them with actual store content before launch.
- No external payment, deployment, email delivery or PostgreSQL/Redis production readiness has been claimed without verification.
