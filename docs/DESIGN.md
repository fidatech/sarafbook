# Design and speed

## The look

SarafBook uses the fida-admin template of Fida Technologies in the formal palette of Rahnuma:
navy `#0a1f3d`, deep blue `#0c3b7a`, gold `#c9a227`; a navy sidebar with the gold lattice, the
open page marked in gold, a gold rule under the top bar. Light and dark mode, English, Dari and
Pashto with a right-to-left style sheet, Vazirmatn / Noto Naskh Arabic / Noto Kufi Arabic
(`FONT_DARI`, `FONT_PASHTO`).

- Shell: `resources/views/layouts/shell.blade.php` with `partials/sidebar`, `partials/topbar`.
  `layouts.admin.base` is the Sarafi's layout, `layouts.platform` the platform panel's,
  `layouts.auth` the sign-in page.
- Parts: `<x-page-header>`, `<x-stat>`, `<x-icon>`, `<x-brand-logo>`, toasts for messages, a
  confirm box on forms with `data-confirm`, page search with Ctrl+K, a tab bar on phones.
- Sources: `resources/assets/css` (Bootstrap 5.3, `fida.css`, `erp.css`, `rasmi.css`,
  `sarafbook.css`) and `resources/assets/js/app.js`. `php tools/build-assets.php` writes
  `public/css/app.css`, `public/css/app.rtl.css`, `public/js/app.js`, `public/icons.svg`,
  keeping only the classes and icons the views use. Write class names in full, or add them to
  `resources/assets/safelist.txt`. `--check` fails when the built files are out of date.

## What is new and what is carried over

New in this design: the shell (sidebar, top bar, search, tab bar), the sign-in page, the
platform panel, messages as toasts.

Carried over: the inner pages of the Sarafi (`resources/views/admin/**`). They are plain
Bootstrap with their own page styles, so they sit inside the new shell and take the new colours
of buttons, cards, forms and tables, but their own layouts, their icon font (Bootstrap Icons)
and their scripts (jQuery, DataTables, Select2, SweetAlert, Chart.js) are as before. Converting
them to the template's parts page by page is the next piece of work; the largest are
`accounts/show` (3,859 lines), `reports/balance` (2,022) and `expenses/index` (996, which also
loads a 4.6 MB DataTables bundle of the earlier template).

## Why pages load fast

- One style sheet of about 28 KB compressed and one 4.5 KB shell script, instead of the 21 files
  of the earlier template; `public/` went from 76 MB to about 11 MB.
- Files are addressed with `?v=<time changed>` and kept by the browser for a year.
- The next page is fetched while the pointer rests on its link (speculation rules, with a
  `<link rel=prefetch>` fallback), and the browser cross-fades between pages (view transitions).
  Links that change something or download a file are never fetched ahead.
- Pages are not marked `no-store`, so Back and Forward are instant.
- The sign-in page and the platform panel load no jQuery at all and defer every script.
- Indexes for the most used filters (`2026_11_16_000001_add_list_indexes`).
- On the server: OPcache, `php artisan optimize`, Redis, compression (see `docs/DEPLOY.md`).

Rahnuma's in-page swapping (`rahnuma-spa.js`) is not used here on purpose: the carried-over
pages declare their scripts inline, and running those twice in one document breaks them.
Prefetching gives nearly the same feel without that risk. It can be switched to in-page swapping
once the inner pages are converted.

## Lighthouse

The sign-in page and the platform panel are built for full scores: one small render-blocking
style sheet, deferred scripts, sized images, labelled fields and buttons, one `h1`, landmarks,
a skip link, visible focus, `lang` and `dir`, title, description, canonical, `robots.txt`,
a manifest, no third-party requests, security headers. `public/llms.txt` describes the site for
software agents.

These scores have **not been measured**: this version has not been run yet. Measure on the
server (HTTPS, compression on) with Lighthouse on `/login` first. The carried-over inner pages
will score lower on Performance until they are converted, mainly because of the libraries they
load themselves.
