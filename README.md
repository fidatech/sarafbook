# SarafBook

Accounts, journal, currency exchange, remittances and reports for Sarafis, in English, Dari and
Pashto. One code base serves every Sarafi; each Sarafi has its own address and its own MySQL
database.

- PHP 8.4, Laravel 13, MySQL 8, Blade. No Node step.
- The look is the fida-admin template in the formal ("Rasmi") palette of Rahnuma.
- The business rules, tables and reports are those of the first SarafBook, unchanged.

The first SarafBook (single Sarafi, earlier admin template) is kept on the branch `v1-legacy`.

## What is where

| Path | What it is |
| --- | --- |
| `app/Tenancy/` | Selecting a Sarafi: database, cache, files, queue, override layer; creating a Sarafi |
| `app/Models/Central/`, `database/migrations/central/` | The platform's own data: Sarafis, addresses, plans, payments, staff, audit log |
| `app/Http/Controllers/Platform/`, `routes/platform.php`, `resources/views/platform/` | The platform panel at `/platform` |
| `app/Http/Controllers/Admin/`, `routes/admin.php`, `resources/views/admin/` | The Sarafi's pages (carried over from the first SarafBook) |
| `database/migrations/tenant/` | Tables of one Sarafi |
| `resources/assets/`, `tools/build-assets.php` | Sources of the design and the script that builds `public/css`, `public/js`, `public/icons.svg` |
| `tenants/<short-name>/` | Changes for one Sarafi only (see `tenants/README.md`) |
| `deploy/` | Nginx and Supervisor examples |
| `docs/` | Tenancy, tenant-only changes, deployment, design and speed |

## First run on a server

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate      # then set the database and domain values
php artisan migrate --force                           # platform tables (central database)
php artisan platform:admin you@example.com            # prints the password once
php artisan tenants:create ahmadi --name="Ahmadi Sarafi" --status=active --expires=2027-11-16
php artisan optimize
```

Open `https://<CENTRAL_DOMAIN>/platform` for the platform panel and the address printed by
`tenants:create` for the Sarafi. Full steps: `docs/DEPLOY.md`.

## One Sarafi without tenancy (local work, tests)

```bash
# .env: TENANCY_ENABLED=false and DB_* of one database
php artisan migrate --seed
php artisan serve
php artisan test
```

Sign in with `admin` / `admin@123` and change the password.

## Commands

| Command | What it does |
| --- | --- |
| `tenants:create <short-name>` | New Sarafi: database, tables, starting data, administrator |
| `tenants:create <short-name> --existing-database` | Bring in a client whose database `sb_<short-name>` was imported from an earlier installation |
| `tenants:migrate [--tenant=a --tenant=b]` | Update the tables of every (or the named) Sarafi, one by one |
| `tenants:run "<artisan command>" [--tenant=a]` | Run a command as each Sarafi, e.g. `tenants:run "balances:recalculate"` |
| `tenants:backup [--keep=30]` | Compressed dump of each database into `storage/backups/<short-name>/` |
| `tenants:list` | Every Sarafi with address, plan, status and end date |
| `platform:admin <email>` | Create a platform administrator or set a new password |
| `php tools/build-assets.php` | Rebuild the CSS, the icon sprite and the script after a design change |

## Status of this version

This version was written without being able to install the Composer packages in the build
environment, so it has passed PHP syntax checks and static checks of views and routes but has
**not yet been run**. Before any client is moved onto it: `composer install`, `php artisan test`
with tenancy off, then create a test Sarafi with tenancy on and walk through every page.
Composer will also write a new `composer.lock`; commit it.
