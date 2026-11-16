# Changes for one Sarafi only

A folder here named after a Sarafi's short name is loaded only for that Sarafi. Nothing in it
touches the main system or any other Sarafi. Everything is optional:

    tenants/<short-name>/
        views/        Blade files; same path as in resources/views, they take its place
                      e.g. views/admin/remittances/print.blade.php = this Sarafi's own receipt
        lang/         fa/..., ps/..., en/... files or fa.json: single lines that differ
        config.php    <?php return ['services.wasender.url' => '...', 'app.name' => '...'];
        bindings.php  <?php return [\App\Services\Something::class => \Tenants\Name\Something::class];
        routes.php    extra pages or reports (inside the "web" and "tenant" middleware)
        boot.php      any other set-up
        migrations/   extra tables or columns; run only on this Sarafi's database

After adding or changing files:

    php artisan tenants:migrate --tenant=<short-name>     (only when migrations/ changed)
    php artisan view:clear
    php tools/build-assets.php                            (only when new CSS classes are used)

See docs/TENANT-CHANGES.md for the three levels (settings, this folder, a pinned version).
