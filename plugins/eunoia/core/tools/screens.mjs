#!/usr/bin/env node
/**
 * Eunoia screenshot tool (AR at 390 and 1280 px; LOCALES=ar,en for layout work).
 *
 * Usage: node plugins/eunoia/core/tools/screens.mjs <TICKET> <name>=<path> [<name>=<path> ...]
 *   e.g. node plugins/eunoia/core/tools/screens.mjs E1-01 login=/admin/login dashboard=/admin
 * Env: LOCALES (default ar), EUNOIA_URL (default http://127.0.0.1:8000), EUNOIA_EMAIL, EUNOIA_PASSWORD,
 *      CHROMIUM_PATH (default /usr/bin/chromium if it exists).
 * Output: docs/eunoia/assets/<TICKET>/<name>-<locale>-<width>.png, plus a list of
 *         requests to Google/Bunny font CDNs or ui-avatars.com (should be empty).
 */
import { createRequire } from 'node:module';
import { existsSync, mkdirSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../../..');
const require = createRequire(join(root, 'tests/e2e-pw/package.json'));
const { chromium } = require('@playwright/test');

const [ticket, ...pairs] = process.argv.slice(2);
if (!ticket || pairs.length === 0) {
    console.error('usage: screens.mjs <TICKET> <name>=<path> ...');
    process.exit(2);
}

const base = process.env.EUNOIA_URL ?? 'http://127.0.0.1:8000';
const email = process.env.EUNOIA_EMAIL ?? 'admin@example.com';
const password = process.env.EUNOIA_PASSWORD ?? 'admin123';
const executablePath = process.env.CHROMIUM_PATH ?? (existsSync('/usr/bin/chromium') ? '/usr/bin/chromium' : undefined);
const outDir = join(root, 'docs/eunoia/assets', ticket);
mkdirSync(outDir, { recursive: true });

const external = /fonts\.googleapis\.com|fonts\.gstatic\.com|fonts\.bunny\.net|ui-avatars\.com/;
const leaks = new Set();
const browser = await chromium.launch({ executablePath });

for (const locale of (process.env.LOCALES ?? 'ar').split(',')) {
    for (const width of [390, 1280]) {
        const context = await browser.newContext({ viewport: { width, height: width === 390 ? 844 : 800 }, locale });
        const page = await context.newPage();
        page.on('request', (r) => { if (external.test(r.url())) leaks.add(r.url()); });

        await page.goto(`${base}/admin/login?lang=${locale}`);
        if (await page.locator('input[type=email]').count()) {
            await page.fill('input[type=email]', email);
            await page.fill('input[type=password]', password);
            await Promise.all([page.waitForURL((u) => !u.pathname.endsWith('/login')), page.click('button[type=submit]')]);
        }

        for (const pair of pairs) {
            const [name, path] = pair.split('=');
            const url = new URL(path, base);
            url.searchParams.set('lang', locale);
            if (name === 'login') {
                const guest = await browser.newContext({ viewport: { width, height: width === 390 ? 844 : 800 }, locale });
                const gp = await guest.newPage();
                gp.on('request', (r) => { if (external.test(r.url())) leaks.add(r.url()); });
                await gp.goto(url.toString(), { waitUntil: 'networkidle' });
                await gp.screenshot({ path: join(outDir, `${name}-${locale}-${width}.png`), fullPage: true });
                await guest.close();
                continue;
            }
            await page.goto(url.toString(), { waitUntil: 'networkidle' });
            await page.screenshot({ path: join(outDir, `${name}-${locale}-${width}.png`), fullPage: width === 390 });
            const dir = await page.getAttribute('html', 'dir');
            console.log(`${name} ${locale} ${width}px dir=${dir}`);
        }
        await context.close();
    }
}

await browser.close();
console.log(leaks.size ? `EXTERNAL REQUESTS:\n${[...leaks].join('\n')}` : 'external font/avatar requests: none');
