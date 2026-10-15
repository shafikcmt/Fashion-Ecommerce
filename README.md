# Fashion Ecommerce

A database-backed Bangla/English fashion storefront and Filament CMS. Laravel 13.35.0, PHP 8.4+, Filament 5.10.1, Livewire 4.4.7, Blade, Tailwind 4.3.3, PostgreSQL 17 and Redis. Packages are pinned in both manifests and lockfiles.

The modular monolith keeps catalog, inventory, checkout and CMS in one application. Database transactions and shared services enforce business rules across storefront and administration. Laravel 13 requires PHP 8.3+; the resolved dependencies use PHP 8.4. Filament 5 supports Laravel 11.28+ and Tailwind 4.1+. Compatibility was checked against [Laravel release notes](https://laravel.com/docs/13.x/releases), [Filament installation](https://filamentphp.com/docs/5.x/introduction/installation), and Composer resolution.

## Docker development

Install Docker Compose. Set a local database password before starting containers.

~~~sh
cp .env.example .env
# Edit DB_PASSWORD in .env.
docker compose up -d postgres redis mailpit app web
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose run --rm assets
docker compose exec app php artisan storage:link
docker compose exec app php artisan commerce:seed-demo
docker compose exec app php artisan commerce:create-admin you@example.com --name="Store Owner"
docker compose up -d worker scheduler
~~~

Store: http://localhost:8080 · Admin: http://localhost:8080/admin · Development inbox: http://localhost:8025.

The administrator command prompts for a password and confirmation without logging either. No administrator or default password is seeded. commerce:seed-demo is opt-in and refuses production. Normal db:seed adds no demo data.

Admin roles: Super Admin manages everything; Catalog Manager manages catalog, images and stock; Order Manager manages orders, shipping, coupons, returns and reports; Content Editor manages pages, media, navigation and branding. Administrative users must have a verified email address.

Upload images in Media, then select them in products or homepage blocks. Settings configure brand name, a hex brand color, contact details, announcement, SEO summary and logo/social image Media IDs. Body content is intentionally plain text and escaped. Products have English/Bangla descriptions, care instructions and size guides.

## Without Docker

Use PHP 8.4+ with pdo_sqlite, intl, gd, fileinfo, mbstring, openssl and zip, Composer and Node 22.12+. Set local .env values:

~~~dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
# DB_DATABASE must point to an existing SQLite file, or omit it for database/database.sqlite.
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=array
MEDIA_DISK=public
~~~

MAIL_MAILER=array discards development messages; use a real local SMTP inbox when testing email delivery. Do not use the log mailer with real customer data.

~~~sh
composer install
# Create database/database.sqlite if absent.
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan storage:link
php artisan commerce:seed-demo
php artisan commerce:create-admin you@example.com
php artisan serve
# Separate terminals:
php artisan queue:work --tries=3
php artisan schedule:work
~~~

This session installed an ignored, checksum-verified Windows PHP 8.4 runtime at .tools/php/php.exe. In this workspace, substitute .\.tools\php\php.exe for php; for Composer use .\.tools\php\php.exe C:\composer\composer.phar. This runtime is not committed. The machine's global PHP 8.2 is too old for the locked dependencies.

## Checks

~~~sh
php vendor/bin/pint --test
php vendor/bin/phpunit
npm run build
php artisan view:cache
php artisan route:cache
php artisan commerce:check-redis
~~~

PHP tests use a disposable in-memory SQLite database by default. To run PostgreSQL tests, explicitly set DB_CONNECTION=pgsql and credentials for a **dedicated disposable test database** before running PHPUnit. Those tests migrate/drop tables and use separate PHP processes to exercise last-item contention, simultaneous retries and coupon limits.

~~~sh
npx playwright install chromium
npm run test:browser
~~~

Browser tests require the development seed and production assets. They launch Laravel on port 8000, create sample COD orders, then cancel them. Use a development database. On Windows with Chrome installed:

~~~powershell
$env:PLAYWRIGHT_CHROME_PATH = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
npm.cmd run test:browser
~~~

GitHub Actions is configured for PHP linting, PostgreSQL tests, Redis connectivity, asset builds, route/view compilation and browser checks on an isolated seeded SQLite database. Configuration is not a claim that remote CI has run.

## Documentation

- [Architecture and business rules](docs/architecture.md)
- [Implemented, tested and pending features](docs/feature-status.md)
- [Deployment, operations, backup and restore](docs/deployment.md)
- [Visual direction](docs/design.md)
- [Demo image sources](docs/media-sources.md)

bKash, Nagad and SSLCommerz are disabled. No provider credentials, payment callback endpoint or pretend successful online payment is included. Supply verified sandbox access before implementing a provider. Manual courier assignment/tracking works; future courier APIs have an interface.

No automatic deployment, default-branch merge or force-push is performed.
