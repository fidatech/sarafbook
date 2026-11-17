# Design and speed

## The look

SarafBook uses the fida-admin template of Fida Technologies exactly as Fida Sarafi does: blue
`#2160d8` with the blue-to-violet brand gradient, a light sidebar, soft cards and tiles. Light and
dark mode, English, Dari and Pashto with a right-to-left style sheet, Vazirmatn / Noto Naskh
Arabic / Noto Kufi Arabic (`FONT_DARI`, `FONT_PASHTO`). `fida.css` and `erp.css` are the files of
Fida Sarafi; copy newer ones over them to follow the template.

- Shell: `resources/views/layouts/shell.blade.php` with `partials/sidebar`, `partials/topbar`.
  `layouts.admin.base` is the Sarafi's layout, `layouts.platform` the platform panel's,
  `layouts.auth` the sign-in page.
- Parts: `<x-page-header>`, `<x-stat>`, `<x-icon>`, `<x-brand-logo>`, toasts for messages, a
  confirm box on forms with `data-confirm`, page search with Ctrl+K, a tab bar on phones.
- Sources: `resources/assets/css` (Bootstrap 5.3, `fida.css`, `erp.css`, `site.css` for
  the public website, `sarafbook.css`) and `resources/assets/js/app.js`. `php tools/build-assets.php` writes
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

## Pages in the fida-sarafi style (every admin page, November 2026)

Every admin page declares `@section('modern', '1')`: no jQuery, DataTables, Select2 or
SweetAlert. A page is drawn on the server; the shared pieces are:

- `page($view, $data, $parts)` (app/Helpers/pages.php): the whole page, or only the
  `@fragment` parts named in the `X-Fragment` header.
- `<form data-live="list,totals">` with `data-live-part="list"` regions: filters, search and
  paging change the list in place (ERP.live in app.js); the address follows, Back works, the
  next page is fetched on hover, the first list is kept.
- `<x-form-modal>` and `<x-field>`: one pop-up for add and edit, opened by
  `data-modal-form="#id"` with `data-fill`, `data-action`, `data-method`; it posts the
  ordinary way and opens again with the errors.
- `done($message, $to, $ok)`: the answer to a save (JSON for old callers, otherwise a
  redirect with the notice; a refused save keeps what was typed). `back_to($default)`:
  return to the page a form was opened from (`back` field, same site only).
- `<select data-search>`: a select you can type in. `ico('name')`: plain icon markup for rows.
- `<x-permission-grid>`: permissions as a grid of parts of the system × actions.
- Uploads: `upload_extension($file)` — images and PDF only, named by their content.
- Lists use `.data-wrap` / `.data-table` / `.row-who` / `.row-name` / `.row-meta` /
  `.amount.is-credit|is-debit` / `.chip-code`; totals `.tx-totals`; cards of choices `.tx-modes`.
