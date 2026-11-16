# Deploying on a VPS

Starting size: 4 vCPU, 8 GB RAM, NVMe disk (an estimate; measure real load before adding Sarafis
by the hundred). Ubuntu LTS, Nginx, PHP-FPM 8.4, MySQL 8, Redis, Supervisor.

PHP extensions: `pdo_mysql mbstring intl gd zip bcmath xml curl redis opcache`.
OPcache: `opcache.enable=1`, `opcache.validate_timestamps=0`, `opcache.memory_consumption=256`
(restart PHP-FPM on each deploy).

## Layout

    /srv/sarafbook/releases/<date>/     each version in its own folder
    /srv/sarafbook/current -> releases/<date>
    /srv/sarafbook/shared/.env          linked into each release
    /srv/sarafbook/shared/storage       linked into each release (tenants/, backups/, logs/)

## First install

    git clone https://github.com/fidatech/sarafbook /srv/sarafbook/releases/2026-11-16
    cd /srv/sarafbook/releases/2026-11-16
    composer install --no-dev --optimize-autoloader
    ln -s /srv/sarafbook/shared/.env .env          # copied from .env.example and filled in
    php artisan key:generate                       # once
    mysql -e "CREATE DATABASE sarafbook_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    php artisan migrate --force
    php artisan platform:admin you@example.com
    php artisan optimize
    ln -sfn /srv/sarafbook/releases/2026-11-16 /srv/sarafbook/current

`.env` on the server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://sarafbook.af`,
`CENTRAL_DOMAINS=sarafbook.af`, `TENANT_BASE_DOMAIN=sarafbook.af`,
`SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`,
`TENANT_DB_CREATE_USER=true`.

The MySQL user in `.env` must be able to create databases (and users, with
`TENANT_DB_CREATE_USER=true`).

Web server: `deploy/nginx.conf`. Queue workers: `deploy/supervisor.conf`.
DNS: `sarafbook.af` and `*.sarafbook.af` to the server; a wildcard certificate for the subdomains.

Cron (as the web user):

    30 1 * * * cd /srv/sarafbook/current && php artisan tenants:backup --keep=30 >/dev/null 2>&1

Copy `storage/backups/` and `storage/tenants/` to storage outside the server every night, and
restore one Sarafi into a scratch database once a month to prove the backups work.

## Each update

    git clone ... releases/<new date> && cd releases/<new date>
    composer install --no-dev --optimize-autoloader
    ln -s /srv/sarafbook/shared/.env .env && rm -rf storage && ln -s /srv/sarafbook/shared/storage storage
    php artisan migrate --force            # platform tables
    php artisan tenants:migrate            # every Sarafi, one by one; a failure does not stop the rest
    php artisan optimize
    ln -sfn $(pwd) /srv/sarafbook/current && sudo systemctl reload php8.4-fpm
    php artisan queue:restart

Rolling back is pointing `current` at the previous release folder.

## Bringing in an existing client

1. Import its database dump as `sb_<short-name>`.
2. `php artisan tenants:create <short-name> --name="..." --existing-database --status=active --expires=YYYY-MM-DD`
   (updates the tables, keeps its users, settings and data).
3. Copy its uploaded files as described in `docs/TENANCY.md`.
4. Compare the balance report and one customer statement with the old system before switching
   the client over.
