# Changing one Sarafi without touching the main system

Use the lowest level that does the job. Each level up costs more to maintain.

## Level 1: settings and feature switches

Stored per Sarafi, changed in the platform panel or in the Sarafi's own settings page:
company name, logo, receipt notes, default language and currency, rounding, plan features
(WhatsApp, exports, own domain, ...), its own WhatsApp key. No code is involved.

## Level 2: the tenant's override folder

`tenants/<short-name>/` is loaded only for that Sarafi (see `tenants/README.md`):

- a view in `views/` takes the place of the same path in `resources/views`;
- a line in `lang/` takes the place of the same translation line;
- `bindings.php` swaps a class (for example its own commission rule);
- `routes.php` adds pages or reports; `migrations/` adds tables or columns to its database only.

The Sarafi still receives every update of the main system, because only the overridden files
differ. This level should carry almost all custom work.

To make a rule overridable, put it in a small class and resolve it from the container
(`app(CommissionRule::class)`) instead of writing it inline; the tenant then binds its own class.

## Level 3: a pinned version

For deep changes, or a client who must stay on an older version:

1. Create the branch `tenant/<short-name>` from the release it should start from.
2. Deploy it to its own folder, `/srv/sarafbook/releases/tenant-<short-name>`, with the same `.env`.
3. Add an Nginx server block for that Sarafi's address pointing at that folder
   (`deploy/nginx.conf` shows where).
4. Set "Version" on the Sarafi in the platform panel to the branch name, so staff can see it.
5. Run its migrations from that folder: `php artisan tenants:migrate --tenant=<short-name>`.

That Sarafi no longer receives main-system updates automatically; merge `main` into its branch
when it should. Price this level accordingly.
