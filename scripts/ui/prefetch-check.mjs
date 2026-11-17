// Checks loading pages early on a live Sarafi (docs/SPEED.md). Prints, does not change data
// except one harmless POST /user/ping (the "online" ping every page already sends).
import { chromium } from 'playwright';

const base = process.env.BASE_URL;
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const early = [];
page.on('request', r => { const h = r.headers()['sec-purpose']; if (h) early.push(new URL(r.url()).pathname + ' [' + h + ']'); });

await page.goto(base + '/lang/en');
await page.goto(base + '/login');
await page.fill('#username', 'admin');
await page.fill('#password', process.env.SARAFI_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('#loginForm button[type=submit]')]);

const timing = () => page.evaluate(() => {
    const n = performance.getEntriesByType('navigation')[0];
    return { page: location.pathname, delivery: n.deliveryType || '(network)', firstByteMs: Math.round(n.responseStart - n.startTime), readyMs: Math.round(n.domContentLoadedEventEnd - n.startTime) };
});

// a few visits so the device learns what this user opens
for (const p of ['/admin/accounts', '/admin/dashboard', '/admin/accounts', '/admin/journal', '/admin/dashboard']) await page.goto(base + p);
await page.waitForLoadState('load');
console.log('opened normally:', JSON.stringify(await timing()));
await page.waitForTimeout(5000);
console.log('pages loaded early:', JSON.stringify(await page.evaluate(() => window.ERP && ERP.earlyPages ? ERP.earlyPages() : null)));
console.log('early requests seen:', JSON.stringify(early));

const target = await page.evaluate(() => (ERP.earlyPages()[0] || '/admin/accounts'));
await Promise.all([page.waitForNavigation(), page.click(`#sidebarMenu a[href$="${target}"], a[href$="${target}"]`)]);
await page.waitForLoadState('load');
console.log('opened from the menu:', JSON.stringify(await timing()));

await page.goto(base + '/admin/expenses');
await page.waitForLoadState('load');
console.log('opened by typing the address (not early):', JSON.stringify(await timing()));

// a save drops the early pages, and the server says so too
await page.waitForTimeout(4500);
const before = await page.evaluate(() => ERP.earlyPages().length);
const saveHeader = await page.evaluate(async () => {
    const r = await fetch('/user/ping', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } });
    return r.headers.get('clear-site-data');
});
await page.waitForTimeout(200);
const after = await page.evaluate(() => ERP.earlyPages().length);
console.log(`after a save: ${before} early pages before, ${after} right after (expected 0), server header: ${saveHeader}`);
await page.waitForTimeout(3500);
console.log('fetched again after the save:', JSON.stringify(await page.evaluate(() => ERP.earlyPages())));

// the server refuses early requests for the never-list, and limits the rest
const refused = await page.request.get(base + '/admin/users/1/update-status', { headers: { 'Sec-Purpose': 'prefetch' }, maxRedirects: 0 });
const allowed = await page.request.get(base + '/admin/accounts', { headers: { 'Sec-Purpose': 'prefetch' }, maxRedirects: 0 });
console.log(`early request for /admin/users/1/update-status: ${refused.status()} (expected 403); for /admin/accounts: ${allowed.status()} (expected 200)`);
await browser.close();
