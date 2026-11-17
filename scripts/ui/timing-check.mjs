// Where the time goes when a page is opened: server, page ready, everything loaded, and what was
// downloaded, for every menu page, opened twice (first visit, then with files in the cache).
import { chromium } from 'playwright';
const base = process.env.BASE_URL;
const browser = await chromium.launch();
const page = await (await browser.newContext({ viewport: { width: 1440, height: 1000 } })).newPage();
await page.goto(base + '/lang/en');
await page.goto(base + '/login');
await page.fill('#username', 'admin');
await page.fill('#password', process.env.SARAFI_PASSWORD);
await Promise.all([page.waitForNavigation(), page.click('#loginForm button[type=submit]')]);
const pages = await page.evaluate(() => [...document.querySelectorAll('#sidebarMenu a.menu-link[href], #sidebarMenu a.menu-sublink[href]')].map(a => new URL(a.href).pathname));
const ping = await page.evaluate(async () => { const t = performance.now(); await fetch('/up', { cache: 'no-store' }); return Math.round(performance.now() - t); });
console.log('round trip to the server (/up):', ping, 'ms');
const measure = () => page.evaluate(() => {
    const n = performance.getEntriesByType('navigation')[0];
    const res = performance.getEntriesByType('resource');
    const net = res.filter(r => r.transferSize > 0);
    const blocking = res.filter(r => r.renderBlockingStatus === 'blocking').map(r => r.name.split('/').pop().split('?')[0]);
    return {
        server: Math.round(n.responseStart - n.requestStart),
        html_kb: Math.round(n.transferSize / 1024),
        shown: Math.round(n.domContentLoadedEventEnd - n.startTime),
        all: Math.round(n.loadEventEnd - n.startTime),
        files: res.length, downloaded: net.length, dl_kb: Math.round(net.reduce((s, r) => s + r.transferSize, 0) / 1024),
        blocking,
    };
});
for (const round of ['first visit', 'second visit']) {
    console.log('--- ' + round);
    for (const p of pages) {
        await page.goto(base + p, { waitUntil: 'load' });
        await page.waitForTimeout(150);
        const m = await measure();
        console.log(`${p.padEnd(42)} server ${String(m.server).padStart(4)} ms | shown ${String(m.shown).padStart(5)} ms | all ${String(m.all).padStart(5)} ms | html ${m.html_kb} KB | ${m.downloaded}/${m.files} files ${m.dl_kb} KB | blocking: ${m.blocking.join(' ')}`);
    }
}
await browser.close();
