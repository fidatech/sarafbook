# Tenancy: one database for each Sarafi

## The two kinds of database

**Central** (`CENTRAL_DB_DATABASE`, connection `central`): `tenants`, `tenant_domains`, `plans`,
`subscription_payments`, `platform_users`, `platform_audits`, plus the sessions of the platform
panel and the queue tables shared by all Sarafis.

**Tenant** (`<TENANT_DB_PREFIX><short-name>`, connection `tenant`): every table of SarafBook
(accounts, transactions, balances, exchanges, remittances, expenses, users, roles, settings,
audit log, sessions, cache). A client's database from the first SarafBook is a valid tenant
database as it is.

## What happens on a request

1. `IdentifyTenant` (first middleware) reads the host name.
2. A host in `CENTRAL_DOMAINS` is the platform: only `/platform` is served there.
3. Any other host is looked up in `tenant_domains` (kept for 60 seconds in a file cache).
   Unknown host: a plain 404 page. Suspended Sarafi: a plain 503 page.
4. `App\Tenancy\Tenancy::initialize()` switches, before the session starts:
   - the default database connection to the Sarafi's database,
   - the cache prefix and the permission cache key,
   - the `local` and `public` disks to `storage/tenants/<id>/app/...`,
   - settings stored on the tenant record (for example its own WhatsApp key),
   - the tenant's override folder `tenants/<short-name>/`, if there is one.
5. The normal SarafBook routes run. They never mention the tenant: the code is the same code
   that ran for a single Sarafi.

Queued jobs carry `tenant_id` in their payload and are switched the same way before they run, so
one pool of workers serves every Sarafi. Commands use `tenancy()->run($tenant, fn () => ...)`.

## Files

Uploads that the first SarafBook wrote into `public/` (logo in `images/`, attachments in
`attachments/` and `assets/attachments/`) now go to `storage/tenants/<id>/public/...` through
`tenant_public_path()`. They are served at the same addresses as before by
`TenantFileController`, so paths stored in the database keep working; attachments need a
signed-in user of that Sarafi. When bringing in an existing client, copy its old
`public/images`, `public/attachments`, `public/assets/attachments` into
`storage/tenants/<id>/public/` and its `storage/app/private` into
`storage/tenants/<id>/app/private`.

## Subscription states

| State | Effect |
| --- | --- |
| `trial` | Works fully until `trial_ends_at`; read-only after it |
| `active` | Works fully until `expires_at`, then `TENANT_GRACE_DAYS` more days with a notice |
| after the grace days | Read-only: sign in, read, print and export work; saving is refused |
| `suspended` | Nobody can sign in; no data is removed |

Recording a renewal in the platform panel stores the payment and moves the end date.

## Plans and features

A plan has a yearly price, a setup fee, a user limit and feature switches. A Sarafi can override
single switches. In code: `feature('whatsapp')`, `tenant()->maxUsers()`. With tenancy off every
feature is on. The user limit is enforced when a user is created.

## Isolation checklist before selling

- `TENANT_DB_CREATE_USER=true` so each Sarafi has a MySQL user limited to its own database.
- Redis for cache, sessions and queue (prefixes are per Sarafi).
- Nightly `tenants:backup` from cron, copied off the server.
- A test that signs in to Sarafi A and requests Sarafi B's records and files (to be added to
  `tests/` once the suite runs with tenancy on).
