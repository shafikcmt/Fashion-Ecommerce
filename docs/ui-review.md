# UI review and polish

Reviewed the actual development storefront before and after at 390, 768, 1024 and 1440 pixels, in English and Bangla: homepage, catalog, product, populated bag, populated checkout and an authenticated customer account. The admin sign-in screen was captured at all four widths. Full captures are retained locally in `.tools/ui-review/before` and `.tools/ui-review/after`; selected comparisons are committed below.

## Changes

- Balanced hero height, responsive product grids, consistent spacing, photograph ratios and sale badges; preserved database-managed homepage and branding content.
- Readable search and header controls, visible mobile wishlist, 44px touch targets, keyboard menu dismissal and a Shop all fallback for empty mobile navigation.
- Collapsible mobile/tablet filters with accessible sorting controls, existing pagination, clearer empty states and a no-image promotion layout.
- Paired product size/color controls, concise quantity input, available-stock feedback, keyboard-scrollable mobile gallery, clearer bag quantity controls and order summaries.
- Locally bundled Cormorant Garamond and Noto Sans Bengali with their OFL licenses. Bangla headings use a separate scale and sufficient line height. Checkout product names now use the selected locale.
- Filament navigation groups and product/variant form sections; explicit English/Bangla, pricing-unit, stock-adjustment and image-upload guidance; clearer dashboard links and admin favicon.

No commerce services, pricing, inventory, payment, coupon or order rules were changed. Online payment integrations remain disabled. No migrations, database resets or record deletion were performed against the existing development database. Browser runs used a separate SQLite backup and ordinary customer registration/authentication.

## Verification

- PHP: 38 passed, 191 assertions; 3 PostgreSQL-specific process tests skipped locally (41 total). PostgreSQL is not available on this host.
- Existing browser tests: four desktop/mobile shopping scenarios passed, including COD order review, invoice and cancellation.
- New browser review: one matrix scenario covers 48 storefront captures plus four admin-login captures, menu keyboard handling, filter expansion, touch targets, image loading and overflow. Its duplicate mobile-project invocation is intentionally skipped.
- Build, required Pint formatter, Blade compilation and whitespace check passed.
- Final review diagnostics reported zero console/JavaScript errors, failed requests or HTTP errors. Initial captures exposed blocked external font requests and a missing admin favicon; both were fixed.

Authenticated admin browser review is blocked: verified admin accounts exist, but authorized credentials were not supplied. No password was guessed, reset, exposed or bypassed. Existing PHP tests verified admin authorization, dashboard/resource rendering, product form mounting, inventory adjustments and order actions. Product/variant editor, upload and content-setting visual checks after sign-in remain outstanding.

## Screenshot comparisons

| Page | Before | After |
| --- | --- | --- |
| Homepage, English, 1440px | [Before](ui-review/before-home-en-1440.png) | [After](ui-review/after-home-en-1440.png) |
| Catalog, English, 390px | [Before](ui-review/before-catalog-en-390.png) | [After](ui-review/after-catalog-en-390.png) |
| Product, English, 768px | [Before](ui-review/before-product-en-768.png) | [After](ui-review/after-product-en-768.png) |
| Checkout, Bangla, 390px | [Before](ui-review/before-checkout-bn-390.png) | [After](ui-review/after-checkout-bn-390.png) |

## View locally

From PowerShell in the project directory (the existing local database and asset installation are already prepared):

~~~powershell
npm.cmd run build
.\.tools\php\php.exe artisan serve --host=127.0.0.1 --port=8000
~~~

Open http://127.0.0.1:8000; sign in to http://127.0.0.1:8000/admin using an existing authorized administrator. The global PHP 8.2 runtime cannot run the locked stack. Use a separate terminal for a queue worker when reviewing queued email with configured SMTP.

To repeat browser checks safely, create a fresh SQLite backup at a different review path, then run the server with `DB_CONNECTION=sqlite` and `DB_DATABASE` pointing to that copy. Run in another terminal:

~~~powershell
$env:PLAYWRIGHT_CHROME_PATH = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
$env:UI_REVIEW_PHASE = 'after'
npm.cmd run test:browser
~~~

The browser tests add sample customers, bags and cancelled COD orders to the selected development database. Do not target production.
