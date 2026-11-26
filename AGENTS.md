# AGENTS.md

## Project
- Laravel 13 / PHP 8.3 stock skeleton: `app/` has only the default `User`, `Controller`, and `AppServiceProvider`; `routes/web.php` only returns the `welcome` view. Local DB is SQLite (`database/database.sqlite`, `DB_CONNECTION=sqlite`) — no MySQL/Redis needed.
- `bootstrap/app.php` renders JSON exceptions for `api/*` or `Accept: application/json` requests.

## Setup & dev
- `composer setup` is the one-shot bootstrap: `composer install` → copy `.env` → `artisan key:generate` → `artisan migrate --force` → `npm install --ignore-scripts` → `npm run build`.
- `composer dev` → `php artisan dev`, which concurrently runs `artisan serve`, `artisan queue:listen --tries=1 --timeout=0`, and `npm run dev` (list them with `php artisan dev:list`). Use it instead of starting servers manually.

## Verification
- `composer test` runs `php artisan config:clear` **then** `php artisan test`; preserve that order.
- `phpunit.xml` forces in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) plus array/sync/null cache, mail, queue, session, and broadcast — tests need no external services. Focus one test with `php artisan test --filter=<name>`.
- Format with `vendor/bin/pint` (default config; no `pint.json`). There is no PHPStan, ESLint, Prettier, or CI workflow — the checks are Pint, `composer test`, and `npm run build`.

## Frontend
- Tailwind 4 is wired through the `@tailwindcss/vite` plugin in `vite.config.js`; there is no `tailwind.config.js` or PostCSS config. `resources/css/app.css` uses `@import 'tailwindcss'`, `@source` globs, and `@theme` — extend the `@source` globs when adding Blade views outside the scanned paths.
- `vite.config.js` loads "Instrument Sans" via `bunny()` from `laravel-vite-plugin/fonts` and ignores `storage/framework/views/**` while watching. `npm run build` produces production assets.
