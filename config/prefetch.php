<?php

/*
| Loading the next pages early (see docs/SPEED.md).
|
| After a page has finished loading and the browser is idle, the pages the user is most likely to
| open next are downloaded in the background: the HTML only, none of their scripts run. When one is
| opened it appears at once. Pages are fetched again after anything is saved, so nothing stale shows.
*/

return [

    'enabled' => (bool) env('PREFETCH', true),

    // Pages fetched after the current page settles (learned from what this user opens most, then
    // the menu). Fewer on slow connections and small devices; none with Data Saver on.
    'eager' => (int) env('PREFETCH_EAGER', 4),

    // Requests for early pages a single user may make in one minute; past it the server answers 429
    // and the browser simply loads that page normally when it is opened.
    'per_minute' => (int) env('PREFETCH_PER_MINUTE', 40),

    // Never fetched ahead: addresses that change something, download a file, open WhatsApp or sign
    // out. URL patterns as the browser's speculation rules read them ("*" is any text).
    'never' => [
        '/logout', '/logout-ping', '/user/ping', '/lang/*', '/platform/lang/*', '/platform/logout',
        '/_platform/*', '/platform/tenants/*/enter',
        '/*recalculate*', '/*refresh*', '/*update-status*', '/*whatsapp*',
        '/*export*', '/*pdf*', '/*print*', '/*download*', '/*template*', '/*receipt*', '/*evidence*',
        '/*delete*', '/*destroy*', '/*market-rate*',
        '/attachments/*', '/assets/attachments/*', '/storage/*', '/images/*',
    ],
];
