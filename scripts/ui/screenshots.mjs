// Signs in to a Sarafi and takes screenshots of a page at phone, tablet and desktop width,
// light and dark, English and Dari, plus a few states. Used by .github/workflows/ops-screenshots.yml.
import { chromium } from 'playwright';
import fs from 'node:fs';

const base = process.env.BASE_URL;            // https://demo.sarafbook.com
const path = process.env.PAGE_PATH || '/admin/accounts';
const out = 'shots';
fs.mkdirSync(out, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, deviceScaleFactor: 1 });
const page = await context.newPage();
await page.goto(base + '/lang/en');
await page.goto(base + '/login');
await page.fill('#username', process.env.SARAFI_USER || 'admin');
await page.fill('#password', process.env.SARAFI_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('#loginForm button[type=submit]')]);
console.log('signed in, now at', new URL(page.url()).pathname);

async function shot(name, { width = 1440, height = 1000, theme = 'light', lang = 'en', url = path, full = true, before } = {}) {
    await page.goto(base + '/lang/' + lang);
    await page.setViewportSize({ width, height });
    await page.addInitScript(t => { try { localStorage.setItem('erp.ui', JSON.stringify({ theme: t })); } catch (e) {} }, theme);
    await page.goto(base + url, { waitUntil: 'networkidle' });
    await page.evaluate(t => { try { localStorage.setItem('erp.ui', JSON.stringify({ theme: t })); } catch (e) {} document.documentElement.setAttribute('data-bs-theme', t); }, theme);
    if (before) await before(page);
    await page.waitForTimeout(400);
    await page.screenshot({ path: `${out}/${name}.jpg`, fullPage: full, type: 'jpeg', quality: 62 });
    console.log('saved', name);
}

await shot('desktop-light-en');
await shot('desktop-dark-en', { theme: 'dark' });
await shot('desktop-light-fa', { lang: 'fa' });
await shot('tablet-light-en', { width: 768, height: 1024 });
await shot('phone-light-en', { width: 375, height: 812 });
await shot('phone-dark-fa', { width: 375, height: 812, theme: 'dark', lang: 'fa' });
await shot('desktop-empty', { url: path + '?q=zzzzzz' });
await shot('desktop-one-currency', { url: path + '?currency=1&balance=debtors' });
// states that exist on some pages only: each is skipped where the page has no such control
const optional = async (name, opts) => { try { await shot(name, opts); } catch (e) { console.log('skipped', name, '-', e.message.split('\n')[0]); } };
await optional('desktop-modal', { full: false, before: async p => { await p.locator('[data-account-new], [data-modal-form]').first().click({ timeout: 3000 }); await p.waitForTimeout(500); } });
await optional('desktop-menu', { full: false, before: async p => { await p.locator('.app-content table [data-bs-toggle="dropdown"]').first().click({ timeout: 3000 }); } });
await optional('desktop-totals-hidden', { full: false, before: async p => { await p.click('#summaryToggle', { timeout: 3000 }); } });
await shot('desktop-keyboard', { full: false, before: async p => { for (let i = 0; i < 9; i++) await p.keyboard.press('Tab'); } });
await shot('desktop-zoom-200', { width: 720, height: 500, full: false });
await browser.close();
