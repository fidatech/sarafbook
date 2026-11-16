# Local page assets and request performance

Page scripts, styles, icon fonts, webfonts and the welcome avatar are served by this application. This removes third-party DNS/TLS/CDN waits from page loading, including when internet access is intermittent. External navigation links (such as WhatsApp) still work normally.

`public/assets/offline/manifest.json` records the original/resolved URLs, local paths and SHA-256 checksums. Deploy the entire directory with the Blade templates. These are checked-in static dependencies: no runtime download or npm build is required for this change. Keep library license headers. When updating a library, refresh its CSS font dependencies, manifest checksums and filename references together.

Shared admin pages load jQuery, Bootstrap and SweetAlert once through the base layout. Standalone login and print pages retain their own dependencies. The invalid Chart.js stylesheet was removed. Persian/Pashto pages use the existing local Vazirmatn font with swap rendering instead of the unavailable Google Fonts Droid Arabic Kufi stylesheet.

Company settings are loaded once per application request scope, including when no settings record exists. No cross-request financial or settings cache was introduced. Apache can cache the vendored assets for seven days and compress text assets using the optional modules configured in that directory's `.htaccess`. Other web servers need equivalent static-file settings for that additional benefit.

Verification:

```sh
python3 scripts/check-offline-assets.py
php artisan test --compact --filter='ViewSettingsPerformanceTest|AccountBulkImportTest|IncomingTrackRemittanceUpdateTest|RemittanceTrackAccountOptionsTest'
node --test tests/js/incoming-remittance-edit.test.mjs
php artisan view:clear
php artisan view:cache
```

The wider default authentication/profile suite has missing settings fixtures and a user-factory/schema mismatch (`email_verified_at`). The existing local npm tools are blocked by macOS executable/native-module policy; the asset changes do not depend on rebuilding Vite. Interactive browser checks and before/after measurements on the affected internet connection remain necessary to quantify the speed improvement.
