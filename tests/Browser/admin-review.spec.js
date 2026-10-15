import { test, expect } from './fixtures.js';
import { phpCommand } from './environment.mjs';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

test.use({ trace: 'off', screenshot: 'off', actionTimeout: 15000 });

function createTemporaryAdmin() {
  const email = 'admin-' + randomUUID() + '@example.test';
  const password = randomUUID() + 'Aa9';
  const result = phpCommand(['artisan', 'commerce:create-admin', email, '--name=Review Administrator'], password + '\n' + password + '\n');
  expect(result.status, 'Administrator command must succeed without logging its output').toBe(0);
  return { email, password };
}

async function signInAsTemporaryAdmin(page, account = createTemporaryAdmin()) {
  const { email, password } = account;
  await page.goto('/admin/login');
  await page.locator('input[type=email]').fill(email);
  await page.locator('input[type=password]').evaluate((input, value) => {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }, password);
  await page.getByRole('button', { name: 'Sign in', exact: true }).evaluate(button => {
    button.click();
    document.querySelector('input[type=password]').value = '';
  });
  await page.waitForURL('**/admin', { waitUntil: 'commit', timeout: 30000 });
  await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible({ timeout: 20000 });
  return { email, password };
}

const modal = page => page.locator('.fi-modal-window').filter({ visible: true });
async function openRowAction(page, text, action) {
  await page.locator('.fi-ta-row').filter({ hasText: text }).getByRole('button', { name: action, exact: true }).click();
  await expect(modal(page)).toBeVisible();
}
async function submitAction(page, feedback) {
  await modal(page).locator('button[type=submit]').click();
  await expect(modal(page)).toHaveCount(0, { timeout: 15000 });
  if (feedback) await expect(page.locator('.fi-no-notification').filter({ hasText: feedback })).toBeVisible();
}
async function selectOption(page, label, option) {
  const field = modal(page).getByRole('combobox', { name: label });
  if (await field.evaluate(element => element.tagName === 'SELECT')) await field.selectOption({ label: option });
  else {
    await field.click();
    await page.getByRole('option', { name: option, exact: true }).click();
  }
}
async function capture(page, name) {
  const directory = path.resolve('.tools/admin-review', process.env.ADMIN_REVIEW_PHASE || 'after');
  await mkdir(directory, { recursive: true });
  await page.screenshot({ path: path.join(directory, name + '.png'), fullPage: true, animations: 'disabled', maskColor: '#e5e7eb', mask: [page.locator('input[type=password]'), page.locator('[data-review-private]'), page.locator('.fi-ta-text-item').filter({ hasText: /@example\.test/ })] });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), name + ': page overflow').toBeTruthy();
}

test('authenticated admin layouts at four widths in disposable environment', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'This review contains the complete viewport matrix.');
  test.setTimeout(300000);
  const errors = [], failures = [], httpErrors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  page.on('requestfailed', request => failures.push(request.url()));
  page.on('response', response => { if (response.status() >= 400) httpErrors.push({ path: new URL(response.url()).pathname, status: response.status() }); });
  await signInAsTemporaryAdmin(page);
  const directory = path.resolve('.tools/admin-review', process.env.ADMIN_REVIEW_PHASE || 'after');
  await mkdir(directory, { recursive: true });
  for (const width of [390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const [name, url] of [['dashboard', '/admin'], ['products', '/admin/products'], ['variants', '/admin/variants'], ['media', '/admin/media'], ['inventory', '/admin/stock-movements'], ['orders', '/admin/orders'], ['returns', '/admin/return-requests'], ['content', '/admin/content-blocks'], ['settings', '/admin/settings'], ['pages', '/admin/pages'], ['reports', '/admin/reports']]) {
      const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
      await capture(page, name + '-' + width);
      expect(response.status(), name + ': HTTP status').toBe(200);
      await expect(page.locator('.fi-page')).toBeVisible();
      await expect.poll(() => page.evaluate(() => Boolean(window.Livewire && window.Alpine))).toBeTruthy();
      if (name === 'dashboard' && width < 1024) {
        const toggle = page.getByRole('button', { name: 'Expand sidebar', exact: true });
        await toggle.focus();
        await page.keyboard.press('Enter');
        await expect(page.getByRole('navigation', { name: 'Sidebar navigation' })).toBeVisible();
        await capture(page, 'navigation-' + width);
        await page.keyboard.press('Escape');
        await expect(page.getByRole('navigation', { name: 'Sidebar navigation' })).not.toBeInViewport();
        await expect(toggle).toBeFocused();
      }
      if (['products', 'variants', 'content'].includes(name)) {
        await page.getByRole('button', { name: /^New / }).click();
        await expect(page.locator('.fi-modal-window').filter({ visible: true })).toBeVisible({ timeout: 15000 });
        if (name === 'products') await modal(page).getByLabel(/Name \(Bangla\)/).fill('আরামদায়ক লিনেন শার্ট');
        if (name === 'content') await modal(page).getByLabel(/Title \(Bangla\)/).fill('প্রতিদিন, নতুন সাজে।');
        await capture(page, name + '-editor-' + width);
        await modal(page).evaluate(element => { element.scrollTop = element.scrollHeight; });
        await capture(page, name + '-editor-bottom-' + width);
        await page.locator('.fi-modal-window').filter({ visible: true }).getByRole('button', { name: 'Cancel', exact: true }).click();
      }
      if (name === 'media') {
        await page.getByRole('button', { name: 'Upload image', exact: true }).click();
        await expect(page.locator('.fi-modal-window').filter({ visible: true })).toBeVisible({ timeout: 15000 });
        await capture(page, 'media-upload-' + width);
        await page.locator('.fi-modal-window').filter({ visible: true }).getByRole('button', { name: 'Cancel', exact: true }).click();
      }
    }
  }
  await writeFile(path.join(directory, 'diagnostics.json'), JSON.stringify({ errors, failures, httpErrors }, null, 2));
  expect(errors).toEqual([]);
  expect(failures).toEqual([]);
  expect(httpErrors).toEqual([]);
});

test('admin saves, uploads, stock, courier and return workflows', async ({ page, browser }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'Reviewed once with the full viewport matrix.');
  test.setTimeout(300000);
  const errors = [], failures = [], httpErrors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  page.on('requestfailed', request => failures.push(request.url()));
  page.on('response', response => { if (response.status() >= 400) httpErrors.push({ path: new URL(response.url()).pathname, status: response.status() }); });
  await signInAsTemporaryAdmin(page);
  await page.setViewportSize({ width: 390, height: 1000 });
  await page.goto('/admin/media');
  await page.getByRole('button', { name: 'Upload image', exact: true }).click();
  await expect(modal(page)).toBeVisible();
  await expect(modal(page).getByLabel(/Image description/)).toBeVisible();
  await modal(page).locator('button[type=submit]').click();
  expect(await modal(page).getByLabel(/Image description/).evaluate(input => input.validity.valueMissing)).toBeTruthy();
  await capture(page, 'upload-validation-390');
  await expect(modal(page).locator('.filepond--root')).toBeVisible({ timeout: 15000 });
  await modal(page).locator('input[type=file]').setInputFiles('public/demo/linen.jpg');
  await expect(modal(page).locator('.filepond--file-status-main')).toContainText('Upload complete', { timeout: 20000 });
  await modal(page).getByLabel(/Image description/).fill('Review linen image');
  await capture(page, 'upload-preview-390');
  await submitAction(page, 'Image uploaded');
  await expect(page.locator('.fi-ta-row').filter({ hasText: 'Review linen image' }).locator('img')).toHaveJSProperty('naturalWidth', 1000);
  await capture(page, 'media-saved-390');

  await page.goto('/admin/products');
  await openRowAction(page, 'The relaxed linen shirt', 'Edit');
  await modal(page).getByLabel(/Name \(English\)/).fill('Reviewed linen shirt');
  await selectOption(page, /^Gallery images/, 'Review linen image');
  await submitAction(page, 'Saved');
  await page.goto('/products/linen-relaxed-shirt');
  await expect(page.getByRole('heading', { name: 'Reviewed linen shirt', exact: true })).toBeVisible();
  await page.getByAltText('Review linen image', { exact: true }).scrollIntoViewIfNeeded();
  await expect(page.getByAltText('Review linen image', { exact: true })).toHaveJSProperty('naturalWidth', 1000);
  await capture(page, 'saved-product-storefront-390');
  await page.goto('/admin/products');
  await openRowAction(page, 'Reviewed linen shirt', 'Edit');
  await modal(page).getByLabel(/Name \(English\)/).fill('The relaxed linen shirt');
  await submitAction(page, 'Saved');

  await page.goto('/admin/variants');
  await page.getByRole('searchbox').fill('AAB-1-M');
  await expect(page.locator('.fi-ta-row')).toHaveCount(1);
  await openRowAction(page, 'AAB-1-M', 'Edit');
  await modal(page).getByLabel(/Low-stock alert/).fill('4');
  await modal(page).getByLabel(/Size/).fill('Review M');
  await modal(page).getByLabel(/Color/).fill('Review blue');
  await submitAction(page, 'Saved');
  await page.goto('/products/linen-relaxed-shirt');
  await page.getByRole('combobox', { name: 'Color', exact: true }).selectOption('Review blue');
  await page.getByRole('combobox', { name: 'Size', exact: true }).selectOption('Review M');
  await expect(page.getByRole('button', { name: 'Add to bag', exact: true })).toBeEnabled();
  await page.goto('/admin/variants');
  await page.getByRole('searchbox').fill('AAB-1-M');
  await expect(page.locator('.fi-ta-row')).toHaveCount(1);
  await openRowAction(page, 'AAB-1-M', 'Edit');
  await modal(page).getByLabel(/Size/).fill('M');
  await modal(page).getByLabel(/Color/).fill('Sky blue');
  await submitAction(page, 'Saved');
  await openRowAction(page, 'AAB-1-M', 'Adjust stock');
  await modal(page).getByLabel(/Stock change/).fill('2');
  await modal(page).getByLabel(/Reason for adjustment/).fill('Disposable review receipt');
  await submitAction(page, 'Stock adjusted');
  await page.goto('/admin/stock-movements');
  await expect(page.locator('.fi-ta-row').filter({ hasText: 'Disposable review receipt' })).toBeVisible();

  await page.goto('/admin/content-blocks');
  await page.getByRole('searchbox').fill('Everyday, beautifully dressed.');
  await expect(page.locator('.fi-ta-row')).toHaveCount(1);
  await openRowAction(page, 'Everyday, beautifully dressed.', 'Edit');
  await modal(page).getByLabel(/Title \(English\)/).fill('A wardrobe reviewed with care.');
  await submitAction(page, 'Saved');
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'A wardrobe reviewed with care.' })).toBeVisible();
  await capture(page, 'saved-content-storefront-390');
  await page.goto('/admin/content-blocks');
  await page.getByRole('searchbox').fill('A wardrobe reviewed with care.');
  await openRowAction(page, 'A wardrobe reviewed with care.', 'Edit');
  await modal(page).getByLabel(/Title \(English\)/).fill('Everyday, beautifully dressed.');
  await submitAction(page, 'Saved');
  await page.getByRole('searchbox').fill('The everyday edit');
  await expect(page.locator('.fi-ta-row')).toHaveCount(1);
  await openRowAction(page, 'The everyday edit', 'Edit');
  await modal(page).getByLabel(/Title \(English\)/).fill('Reviewed edit');
  await submitAction(page, 'Saved');
  await page.goto('/');
  await page.locator('.mobile-nav summary').click();
  await expect(page.locator('.mobile-nav').getByRole('link', { name: 'Reviewed edit', exact: true })).toBeVisible();
  await page.goto('/admin/content-blocks');
  await page.getByRole('searchbox').fill('Reviewed edit');
  await openRowAction(page, 'Reviewed edit', 'Edit');
  await modal(page).getByLabel(/Title \(English\)/).fill('The everyday edit');
  await submitAction(page, 'Saved');
  await page.goto('/admin/settings');
  await page.getByRole('searchbox').fill('brand_name');
  await expect(page.locator('.fi-ta-row')).toHaveCount(1);
  await openRowAction(page, 'brand_name', 'Edit');
  await modal(page).getByLabel(/Setting value/).fill('Reviewed Aabaran');
  await submitAction(page, 'Saved');
  await page.goto('/');
  await expect(page.locator('header .wordmark')).toContainText('Reviewed Aabaran');
  await page.goto('/admin/settings');
  await page.getByRole('searchbox').fill('brand_name');
  await openRowAction(page, 'brand_name', 'Edit');
  await modal(page).getByLabel(/Setting value/).fill('Aabaran');
  await submitAction(page, 'Saved');

  const customerContext = await browser.newContext({ baseURL: process.env.APP_URL });
  const customer = await customerContext.newPage();
  await customer.goto('/pages/' + process.env.BROWSER_REVIEW_MARKER);
  await expect(customer.locator('main')).toContainText(process.env.BROWSER_REVIEW_MARKER);
  await customer.goto('/products/linen-relaxed-shirt');
  await customer.getByRole('combobox', { name: 'Color', exact: true }).selectOption('Sky blue');
  await customer.getByRole('combobox', { name: 'Size', exact: true }).selectOption('M');
  await customer.getByRole('button', { name: 'Add to bag', exact: true }).click();
  await expect(customer.getByText('Added to your bag.', { exact: false })).toBeVisible();
  await customer.goto('/checkout');
  for (const [label, value] of [['Full name', 'Review Buyer'], ['Email', 'review-buyer@example.test'], ['Phone', '01700000000'], ['District', 'Dhaka']]) await customer.getByLabel(label, { exact: true }).fill(value);
  await customer.getByLabel('Street address').fill('Disposable review address');
  await customer.getByRole('combobox', { name: 'Shipping method', exact: true }).selectOption({ label: 'Dhaka delivery · ৳80 (Dhaka)' });
  await customer.locator('input[name=terms]').check();
  await customer.getByRole('button', { name: 'Review your order', exact: true }).click();
  await customer.getByRole('button', { name: 'Place order · Cash on delivery', exact: true }).click();
  await expect(customer.getByRole('heading', { name: 'Order received' })).toBeVisible();
  const receipt = customer.url();
  await page.goto('/admin/orders');
  await openRowAction(page, 'Review Buyer', 'Assign courier');
  await modal(page).getByLabel(/Courier/).fill('Review courier');
  await modal(page).getByLabel(/Tracking number/).fill('REVIEW-TRACKING');
  await submitAction(page, 'Courier assigned');
  for (const [action, feedback] of [['Confirm order', 'Order confirmed'], ['Mark shipped', 'Order shipped'], ['Mark delivered', 'Order delivered'], ['Record COD collected', 'COD collection recorded']]) {
    await openRowAction(page, 'Review Buyer', action);
    await submitAction(page, feedback);
  }
  await customer.goto(receipt);
  await customer.getByText('Request a return', { exact: true }).click();
  await customer.locator('textarea[name=reason]').fill('Disposable review return for fit.');
  await customer.getByRole('button', { name: 'Request return', exact: true }).click();
  await expect(customer.getByText('requested', { exact: false }).first()).toBeVisible();
  await page.goto('/admin/return-requests');
  await page.locator('.fi-ta-row').first().getByRole('button', { name: 'Approve return', exact: true }).click();
  await modal(page).getByLabel(/Decision note/).fill('Review return approved after inspection.');
  await submitAction(page, 'Return approved');
  await page.locator('.fi-ta-row').first().getByRole('button', { name: 'Confirm received & restock', exact: true }).click();
  await submitAction(page, 'Return received and stock restored');
  await page.goto('/admin/orders');
  await openRowAction(page, 'Review Buyer', 'Record manual refund');
  await submitAction(page, 'Manual refund recorded');
  await customer.goto(receipt);
  await expect(customer.locator('.status-grid')).toContainText('Refunded');
  await customerContext.close();
  for (const width of [390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    for (const [name, url] of [['inventory-populated', '/admin/stock-movements'], ['orders-populated', '/admin/orders'], ['returns-populated', '/admin/return-requests']]) {
      await page.goto(url);
      await capture(page, name + '-' + width);
    }
    await page.goto('/admin/orders');
    await openRowAction(page, 'Review Buyer', 'Details');
    await capture(page, 'order-details-' + width);
    await modal(page).getByRole('button', { name: 'Cancel', exact: true }).click();
  }
  await writeFile(path.resolve('.tools/admin-review', process.env.ADMIN_REVIEW_PHASE || 'after', 'workflow-diagnostics.json'), JSON.stringify({ errors, failures, httpErrors }, null, 2));
  expect(errors).toEqual([]);
  expect(failures).toEqual([]);
  expect(httpErrors).toEqual([]);
});


test('staff roles keep their authorized navigation and reject other resources', async ({ page, browser }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'Role checks are independent of device emulation.');
  test.setTimeout(180000);
  await signInAsTemporaryAdmin(page);
  for (const [role, label, allowed, denied] of [
    ['catalog_manager', 'Catalog Manager', ['products', 'variants', 'media', 'stock-movements'], ['orders', 'settings', 'users']],
    ['order_manager', 'Order Manager', ['orders', 'return-requests', 'reports'], ['products', 'media', 'settings', 'users']],
    ['content_editor', 'Content Editor', ['content-blocks', 'settings', 'media'], ['products', 'orders', 'users']],
  ]) {
    const account = createTemporaryAdmin();
    await page.goto('/admin/users');
    await page.getByRole('searchbox').fill(account.email);
    await expect(page.locator('.fi-ta-row')).toHaveCount(1);
    await openRowAction(page, account.email, 'Edit');
    await selectOption(page, /^Role/, label);
    await submitAction(page, 'Saved');
    const context = await browser.newContext({ baseURL: process.env.APP_URL });
    const staff = await context.newPage();
    await staff.goto('/pages/' + process.env.BROWSER_REVIEW_MARKER);
    await expect(staff.locator('main')).toContainText(process.env.BROWSER_REVIEW_MARKER);
    await signInAsTemporaryAdmin(staff, account);
    for (const slug of allowed) expect((await context.request.get('/admin/' + slug)).status(), role + ' allows ' + slug).toBe(200);
    for (const slug of denied) {
      expect((await context.request.get('/admin/' + slug)).status(), role + ' forbids ' + slug).toBe(403);
      await expect(staff.locator('.fi-sidebar-nav a[href$="/admin/' + slug + '"]')).toHaveCount(0);
    }
    for (const width of [390, 1440]) {
      await staff.setViewportSize({ width, height: 1000 });
      await capture(staff, role + '-' + width);
    }
    await context.close();
  }
});
