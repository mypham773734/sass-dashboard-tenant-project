import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8765';
const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext();
const page = await ctx.newPage();

// Login
await page.goto(`${BASE}/login`);
await page.fill('input[name=email]', 'test@example.com');
await page.fill('input[name=password]', 'password123');
await page.click('button[type=submit]');
await page.waitForURL(`${BASE}/admin`, { timeout: 8000 });

// Inspect Livewire state on page
const lwInfo = await page.evaluate(() => {
    const info = {
        livewireExists: typeof window.Livewire !== 'undefined',
        livewireKeys: window.Livewire ? Object.keys(window.Livewire) : [],
        wireIdElements: [],
        wireNameElements: [],
    };

    document.querySelectorAll('[wire\\:id]').forEach(el => {
        info.wireIdElements.push({
            id: el.getAttribute('wire:id'),
            name: el.getAttribute('wire:name') || el.getAttribute('name') || 'unknown',
            tag: el.tagName,
        });
    });

    return info;
});

console.log('Livewire exists:', lwInfo.livewireExists);
console.log('Livewire keys:', lwInfo.livewireKeys.join(', '));
console.log('wire:id elements:', JSON.stringify(lwInfo.wireIdElements, null, 2));

// Try to get all Livewire components
const components = await page.evaluate(() => {
    if (!window.Livewire) return [];
    try {
        const all = window.Livewire.all ? window.Livewire.all() : [];
        return all.map(c => ({
            id: c.id,
            name: c.name || c.component?.name,
            keys: Object.keys(c),
        }));
    } catch(e) {
        return [{ error: e.message }];
    }
});
console.log('Components:', JSON.stringify(components, null, 2));

// Try direct call to switchTenant(2) using different Livewire 4 APIs
const result = await page.evaluate(() => {
    if (!window.Livewire) return 'Livewire not found';

    try {
        // Try Livewire v3 API
        const all = window.Livewire.all?.() || [];
        const comp = all.find(c => c.name?.includes('tenant') || c.id);
        if (comp) {
            if (comp.call) {
                comp.call('switchTenant', 2);
                return 'called via comp.call()';
            }
            if (comp.$wire?.switchTenant) {
                comp.$wire.switchTenant(2);
                return 'called via $wire.switchTenant()';
            }
        }

        // Try finding by wire:id
        const el = document.querySelector('[wire\\:id]');
        const wireId = el?.getAttribute('wire:id');
        if (wireId) {
            const component = window.Livewire.find(wireId);
            if (component) {
                if (component.call) {
                    component.call('switchTenant', 2);
                    return 'called via find().call()';
                }
                if (component.$wire?.switchTenant) {
                    component.$wire.switchTenant(2);
                    return 'called via find().$wire';
                }
            }
            return `find(${wireId}) returned: ${JSON.stringify(component ? Object.keys(component) : null)}`;
        }
        return 'no wire:id found';
    } catch(e) {
        return 'Error: ' + e.message;
    }
});
console.log('Injection result:', result);

await page.waitForTimeout(2000);
const html = await page.content();
const hasError = html.includes('không hoạt động');
console.log('Error in HTML after injection:', hasError);

await browser.close();
