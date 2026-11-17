# Pages that open at once, without being a single-page app

Every page of SarafBook is an ordinary page: its own address, its own HTML from the server,
Back and Forward and reload work as always, and nothing is drawn by a client-side framework.
What makes it feel like an app is that the next page is usually ready before it is opened.

## The four parts

| Part | What happens | Where |
| --- | --- | --- |
| 1. Likely pages | Once the page has fully loaded and the browser is idle, the pages this user opens most (learned on this device), then the menu, are fetched in the background. HTML only; none of their scripts run. 4 pages by default, 2 on a 3G connection or a device with little memory, none with Data Saver or on 2G. Nothing happens while the tab is hidden. | `resources/assets/js/app.js` (moving between pages) |
| 2. Hover | The page under the pointer is fetched after a short rest on its link. | `resources/views/partials/scripts.blade.php` |
| 3. Shared files | Libraries and styles that other pages need (`public/warm.json`, written by `tools/build-assets.php`) are downloaded once; the browser keeps them for a year. | `app.js`, `App\Support\WarmAssets` |
| 4. Always fresh | A save (form, `fetch` or jQuery) drops every early page; they are fetched again 2.5 s after the save. The server also answers every save with `Clear-Site-Data: "prefetchCache", "prerenderCache"`. A save in another tab does the same. A page brought back by Back is shown at once, or reloaded if something was saved after it was first shown. | `app.js`, `App\Http\Middleware\PrefetchGuard` |

## Safety and load

- **Never-list** (`config/prefetch.php`): addresses that change something, download a file, open
  WhatsApp or sign out are never fetched ahead. The browser's rules and the shell script read the
  list, and the server refuses an early request for any of them with 403, so a mistake in a
  link cannot make a change happen.
- Early requests carry `Sec-Purpose: prefetch`. The server allows `PREFETCH_PER_MINUTE` (40) of
  them per user per minute and answers 429 past it; the page then loads normally when opened.
- Pages are fetched, not pre-rendered: the old pages run code when they load, and that code must
  run only when the user actually opens the page.
- `PREFETCH=false` in `.env` switches all of it off.

## Browsers

Parts 1 and 2 use the browser's speculation rules: Chrome, Edge, Opera, Samsung Internet and
Chrome on Android, which is what most Sarafis use. Safari and Firefox load pages the normal
way and still get parts 3 and 4 (shared files ready, instant Back and Forward).

## Checking it

Run the **Screenshots** workflow with `check = prefetch`. It signs in to the demo, opens the
dashboard, waits, lists the pages loaded early, opens one of them and prints how the browser got
it (`navigational-prefetch` means from the early load) and how long it took, then makes a save
and checks the early pages are dropped, and sends an early request for a forbidden address to
check it is refused.
