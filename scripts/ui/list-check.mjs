// Checks the Accounts list that changes in place (search, filters, pages, Back, edit on new
// rows) on a live Sarafi, with a slow connection like a phone in Afghanistan. Changes nothing.
import { chromium } from 'playwright';

const base = process.env.BASE_URL;
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const errors = [];
page.on('pageerror', e => errors.push(e.message));
let navigations = 0;
page.on('framenavigated', f => { if (f === page.mainFrame()) navigations++; });

await page.goto(base + '/lang/en');
await page.goto(base + '/login');
await page.fill('#username', 'admin');
await page.fill('#password', process.env.SARAFI_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('#loginForm button[type=submit]')]);

// about 300 ms each way and 1.5 Mbit/s, like many connections there
const cdp = await context.newCDPSession(page);
await cdp.send('Network.enable');
await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 300, downloadThroughput: 190000, uploadThroughput: 90000 });

const t0 = Date.now();
await page.goto(base + '/admin/accounts');
await page.waitForLoadState('load');
console.log('whole page on a slow connection:', Date.now() - t0, 'ms');

const rows = () => page.locator('#accResults tbody tr:not(.acc-group)').count();
const waitList = async () => { await page.waitForFunction(() => !document.querySelector('#accResults.is-loading')); };
const step = async (name, fn) => {
    const before = navigations, t = Date.now();
    await fn();
    await page.waitForTimeout(50);
    await waitList();
    console.log(`${name}: ${Date.now() - t} ms, rows ${await rows()}, address ${new URL(page.url()).search || '(none)'}, whole page loaded: ${navigations > before ? 'YES' : 'no'}`);
};

await step('type a search', async () => { await page.fill('#accountFilters input[name=q]', 'CUS'); await page.waitForTimeout(400); });
await step('choose a currency', async () => { await page.selectOption('#accountFilters select[name=currency]', { index: 1 }); });
await step('debtors first', async () => { await page.selectOption('#accountFilters select[name=balance]', 'debtors'); });
await step('clear the filters', async () => { await page.click('#accClear'); });
await step('10 rows a page', async () => { await page.selectOption('#accountFilters select[name=per_page]', '10'); });
const next = page.locator('#accResults .pagination a[href*="page=2"]').first();
if (await next.count()) {
    await next.hover(); await page.waitForTimeout(900);
    await step('page 2 (fetched on hover)', async () => { await next.click(); });
}
await step('Back', async () => { await page.goBack(); });
console.log('search box after Back:', JSON.stringify(await page.inputValue('#accountFilters input[name=q]')), 'rows a page:', await page.inputValue('#accountFilters select[name=per_page]'));

// the edit button of a row that came in place still opens the form
await page.locator('#accResults [data-account-edit]').first().click();
await page.waitForSelector('#accountModal.show', { timeout: 5000 }).then(() => console.log('edit form opens on a new row: yes'), () => console.log('edit form opens on a new row: NO'));
console.log('script errors:', JSON.stringify(errors));
await browser.close();
