# Local Development

## Environment
- APP_ENV=local, APP_DEBUG=true, BCRYPT_ROUNDS=12, LOG_CHANNEL=stack
- DB_CONNECTION=mysql (local) or sqlite :memory: for testing (phpunit.xml)
- SESSION_DRIVER=database, QUEUE_CONNECTION=database, CACHE_STORE=database

## Workflow
- `php artisan serve` for dev server
- `php artisan migrate:fresh --seed` to reset
- `npm run dev` for Vite dev server (auto-reload)
- `php artisan test` or `vendor/bin/pest` for tests
- `vendor/bin/pint` for style

## Seeders
- RolePermissionSeeder only in local/testing, creates 5 roles + demo users
- Never run in production (checks app()->environment)

## Vite
- `vite.config.js` default, `resources/css/app.css` Tailwind + `js/app.js`, Bootstrap via CDN in layouts/bootstrap
- `npm run build` generates public/build/manifest.json

## Testing
- Pest, RefreshDatabase, sqlite :memory:
- `php artisan test --filter=DashboardFoundationTest` for Phase5 tests
- `php artisan test` runs 49 tests

## Git
- Dashboard is not a separate git repo, part of main repo
- `.env` ignored, `.env.example` committed

## AI Notice
- Always visible in layout and Help page
