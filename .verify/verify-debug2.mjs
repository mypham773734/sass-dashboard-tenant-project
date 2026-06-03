import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext();
const page = await ctx.newPage();

// Capture all console messages
page.on('console', m => console.log(`[browser ${m.type()}] ${m.text()}`));

await page.goto(`${BASE}/login`);
await page.fill('input[name=email]', 'test@example.com');
await page.fill('input[name=password]', 'password123');
await page.click('button[type=submit]');
await page.waitForURL(`${BASE}/admin`, { timeout: 8000 });

// Wait for Livewire to finish initial render
await page.waitForTimeout(2000);

const info = await page.evaluate(() => {
    const wireEl = document.querySelector('[wire\\:id]');
    const wireId = wireEl?.getAttribute('wire:id');

    // Check Livewire internal state
    const compByFind = wireId ? window.Livewire?.find(wireId) : null;
    const compByName = window.Livewire?.getByName('tenant-switcher');
    const compFirst   = window.Livewire?.first?.('tenant-switcher');

    // Check Alpine's internal store on the element
    const alpineData = wireEl ? wireEl._x_dataStack : null;

    return {
        wireId,
        livewireVersion: window.livewire_app_url || 'unknown',
        findResult: compByFind ? Object.keys(compByFind) : null,
        getByNameResult: compByName ? (Array.isArray(compByName) ? compByName.map(c => Object.keys(c)) : Object.keys(compByName)) : null,
        firstResult: compFirst ? Object.keys(compFirst) : null,
        alpineStackLength: alpineData?.length,
        initialRenderDone: window.Livewire?.initialRenderIsFinished,
        // Check if wire:id div has __livewire property
        hasLivewireProp: wireEl ? (typeof wireEl.__livewire) : 'no element',
        hasWireProperty: wireEl ? (typeof wireEl.__wire) : 'no element',
        livewireAllCount: window.Livewire?.all?.()?.length,
    };
});

console.log('Debug info:', JSON.stringify(info, null, 2));

// Try getByName
const callResult = await page.evaluate(() => {
    const comps = window.Livewire?.getByName?.('tenant-switcher');
    if (comps && comps.length > 0) {
        const comp = comps[0];
        console.log('[call] Calling switchTenant(2) via getByName');
        if (comp.call) {
            comp.call('switchTenant', 2);
            return 'called via getByName[0].call()';
        }
        if (comp.$wire) {
            comp.$wire.switchTenant(2);
            return 'called via getByName[0].$wire';
        }
        return 'comp exists but no call method: ' + Object.keys(comp).join(',');
    }
    return 'getByName returned empty or null: ' + JSON.stringify(comps);
});
console.log('Call result:', callResult);

await page.waitForTimeout(3000);
const html = await page.content();
console.log('Error in HTML:', html.includes('không hoạt động') || html.includes('không có quyền'));

await browser.close();
