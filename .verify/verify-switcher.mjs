import { chromium } from 'playwright';
import { writeFileSync } from 'fs';

const BASE = 'http://127.0.0.1:8765';
const EMAIL = 'test@example.com';
const PASSWORD = 'password123';

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext();
const page = await ctx.newPage();

const log = (msg) => console.log(msg);
const shot = async (name) => {
    const p = `verify-${name}.png`;
    await page.screenshot({ path: p, fullPage: false });
    log(`  📸 ${p}`);
};

// ── 1. Login ─────────────────────────────────────────────────────────────────
log('\n[1] Login');
await page.goto(`${BASE}/login`);
await page.fill('input[name=email]', EMAIL);
await page.fill('input[name=password]', PASSWORD);
await page.click('button[type=submit]');
await page.waitForURL(`${BASE}/admin`, { timeout: 8000 });
log('  ✅ Redirected to /admin');
await shot('01-dashboard');

// ── 2. Verify component mounts without PHP error ──────────────────────────────
log('\n[2] Component mount check');
const switcher = page.locator('[x-data]').filter({ hasText: /workspace|tenant/i }).first();
const triggerBtn = page.locator('button').filter({ has: page.locator('.fa-chevron-down') }).first();
const btnText = await triggerBtn.textContent();
log(`  Trigger button text: "${btnText?.trim()}"`);
const hasError = await page.locator('.text-red-700, .exception').count();
log(`  PHP/Livewire errors on page: ${hasError}`);

// ── 3. Dropdown opens/closes with Alpine.js ────────────────────────────────
log('\n[3] Dropdown toggle (Alpine.js)');
await triggerBtn.click();
await page.waitForTimeout(200);
const dropdownVisible = await page.locator('div[x-show]').first().isVisible();
log(`  Dropdown visible after click: ${dropdownVisible}`);
await shot('02-dropdown-open');

await page.keyboard.press('Escape');
await page.click('body');
await page.waitForTimeout(200);
const dropdownHidden = !(await page.locator('div[x-show]').first().isVisible().catch(() => false));
log(`  Dropdown hidden after click-outside: ${dropdownHidden}`);

// ── 4. Active tenant shows checkmark ──────────────────────────────────────
log('\n[4] Active tenant indicator');
await triggerBtn.click();
await page.waitForTimeout(200);
const checkmarks = await page.locator('.fa-check').count();
log(`  Checkmark icons in dropdown: ${checkmarks}`);
const inactiveBadges = await page.locator('span:text("Inactive")').count();
log(`  Inactive badges: ${inactiveBadges}`);
await shot('03-dropdown-items');

// ── 5. Block inactive tenant ───────────────────────────────────────────────
log('\n[5] Block inactive tenant (is_active=false)');
const inactiveBtn = page.locator('button[wire\\:click]').filter({ has: page.locator('span:text("Inactive")') });
const inactiveBtnCount = await inactiveBtn.count();
log(`  Inactive tenant buttons found: ${inactiveBtnCount}`);

if (inactiveBtnCount > 0) {
    // Capture console messages and network failures
    const consoleErrors = [];
    const networkFails = [];
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push(m.text()); });
    page.on('requestfailed', req => networkFails.push(`${req.method()} ${req.url()} — ${req.failure()?.errorText}`));
    const lwRequests = [];
    page.on('response', resp => {
        // Catch all POST/XHR responses to find Livewire endpoint
        if (!resp.url().includes('.css') && !resp.url().includes('.js') && !resp.url().includes('.png')) {
            lwRequests.push(`${resp.status()} ${resp.request().method()} ${resp.url()}`);
        }
    });

    // Ensure dropdown is open (step 4 may have left it open — check first, only open if closed)
    const isAlreadyOpen = await page.locator('div[x-show]').first().isVisible();
    if (!isAlreadyOpen) {
        await triggerBtn.click();
        await page.waitForTimeout(200);
    }
    const isDropdownOpen = await page.locator('div[x-show]').first().isVisible();
    log(`  Dropdown open before click: ${isDropdownOpen}`);

    // Try click first; if no request after 1s, fall back to JS injection
    await inactiveBtn.first().click({ force: true });
    await page.waitForTimeout(1500);

    // If click produced no Livewire request, call via JS directly
    if (lwRequests.length === 0) {
        log('  ⚠️  No request after click — trying JS injection');
        const inactiveTenantId = await inactiveBtn.first().evaluate(btn => {
            const m = btn.getAttribute('wire:click')?.match(/switchTenant\((\d+)\)/);
            return m ? parseInt(m[1]) : null;
        });
        log(`  Inactive tenant ID: ${inactiveTenantId}`);
        if (inactiveTenantId) {
            await page.evaluate((tid) => {
                const el = document.querySelector('[wire\\:id]');
                const wireId = el?.getAttribute('wire:id');
                if (wireId && window.Livewire) {
                    window.Livewire.find(wireId)?.call('switchTenant', tid);
                }
            }, inactiveTenantId);
        }
    }
    await page.waitForTimeout(2000);
    await shot('04-inactive-error-toast');

    // Check for toast — try multiple selectors
    const toastByBg   = page.locator('.bg-red-50');
    const toastByText = page.locator('text=Workspace này hiện không hoạt động');
    const toast2Text  = page.locator('text=không có quyền');
    const toastVisible = await toastByBg.isVisible().catch(() => false);
    const byTextVisible = await toastByText.isVisible().catch(() => false);
    const byText2Visible = await toast2Text.isVisible().catch(() => false);
    log(`  .bg-red-50 visible: ${toastVisible}`);
    log(`  text "không hoạt động" visible: ${byTextVisible}`);
    log(`  text "không có quyền" visible: ${byText2Visible}`);
    if (toastVisible) {
        log(`  Toast text: "${(await toastByBg.textContent())?.trim()}"`);
    }
    if (consoleErrors.length) log(`  Console errors: ${consoleErrors.join('; ')}`);
    log(`  Livewire requests: ${lwRequests.length ? lwRequests.join(', ') : 'none'}`);
    if (networkFails.length) log(`  Network failures: ${networkFails.join('; ')}`);

    // Check page HTML for errorMessage
    const pageHtml = await page.content();
    const hasErrorInHtml = pageHtml.includes('không hoạt động') || pageHtml.includes('không có quyền');
    log(`  Error text in HTML: ${hasErrorInHtml}`);

    const stillOnAdmin = page.url().includes('/admin');
    log(`  Still on /admin (no redirect): ${stillOnAdmin}`);
} else {
    log('  ⚠️  No inactive tenant found in dropdown — skipping block test');
}

// ── 6. Switch to active tenant ─────────────────────────────────────────────
log('\n[6] Switch to active tenant → redirect /admin');
// Re-open dropdown
await triggerBtn.click();
await page.waitForTimeout(200);
const activeBtns = page.locator('button[wire\\:click]').filter({ hasNot: page.locator('span:text("Inactive")') });
const activeBtnCount = await activeBtns.count();
log(`  Active tenant buttons: ${activeBtnCount}`);

if (activeBtnCount > 0) {
    await activeBtns.first().click();
    try {
        await page.waitForURL(`${BASE}/admin`, { timeout: 5000 });
        log('  ✅ Redirected to /admin after switch');
    } catch {
        log(`  ⚠️  URL after switch: ${page.url()}`);
    }
    await shot('05-after-switch');
}

// ── 7. Probe: try switching to tenant not belonging to user ───────────────
log('\n[7] Probe: switchTenant via direct Livewire call with non-existent ID');
// Inject a Livewire call for a tenant ID that doesn't exist (ID=9999)
await page.evaluate(() => {
    const lw = document.querySelector('[wire\\:id]');
    if (lw) {
        const wireId = lw.getAttribute('wire:id');
        // Use Livewire's global to call the method
        if (window.Livewire) {
            window.Livewire.find(wireId)?.call('switchTenant', 9999);
        }
    }
});
await page.waitForTimeout(1500);
const probeToast = page.locator('.bg-red-50');
const probeToastVisible = await probeToast.isVisible().catch(() => false);
log(`  Error toast for non-existent tenant: ${probeToastVisible}`);
if (probeToastVisible) {
    const probeText = await probeToast.textContent();
    log(`  Toast text: "${probeText?.trim()}"`);
    await shot('06-probe-invalid-tenant');
}

await browser.close();
log('\n✅ Verification complete');
