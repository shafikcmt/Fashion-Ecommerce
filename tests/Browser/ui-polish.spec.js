import { test, expect } from './fixtures.js';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

test('storefront review across English, Bangla and four widths', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop', 'The review covers its own viewport matrix.');
  test.setTimeout(300000);
  const errors = [], failed = [], responses = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
  page.on('requestfailed', request => failed.push({ url: request.url(), error: request.failure()?.errorText }));
  page.on('response', response => { if (response.status() >= 400) responses.push({ url: response.url(), status: response.status() }); });
  await page.goto('/register');
  await page.getByLabel('Full name', { exact: true }).fill('UI review customer');
  await page.getByLabel('Email', { exact: true }).fill(`ui-${randomUUID()}@example.test`);
  const password = randomUUID() + 'Aa9';
  await page.getByLabel('Password', { exact: true }).fill(password);
  await page.getByLabel('Confirm password', { exact: true }).fill(password);
  await page.getByRole('button', { name: 'Create account', exact: true }).click();
  await page.waitForURL('**/verify-email');
  await page.goto('/products/linen-relaxed-shirt');
  await page.getByRole('combobox', { name: 'Color', exact: true }).selectOption('Sky blue');
  await page.getByRole('combobox', { name: 'Size', exact: true }).selectOption('M');
  await page.getByRole('button', { name: 'Add to bag', exact: true }).click();
  await expect(page.getByText('Added to your bag.', { exact: false })).toBeVisible();
  const phase = process.env.UI_REVIEW_PHASE;
  const directory = phase ? path.resolve('.tools/ui-review', phase) : testInfo.outputPath('review');
  await mkdir(directory, { recursive: true });
  for (const locale of ['en', 'bn']) {
    if (locale === 'bn') {
      await page.getByRole('button', { name: 'বাংলা', exact: true }).click();
      await expect(page.locator('html')).toHaveAttribute('lang', 'bn');
    }
    for (const width of [390, 768, 1024, 1440]) {
      await page.setViewportSize({ width, height: 1000 });
      for (const [name, url] of [['home', '/'], ['catalog', '/shop'], ['product', '/products/linen-relaxed-shirt'], ['bag', '/bag'], ['checkout', '/checkout'], ['account', '/account']]) {
        await page.goto(url);
        await page.evaluate(async () => {
          await document.fonts.ready;
          for (let y = 0; y < document.body.scrollHeight; y += 700) { window.scrollTo(0, y); await new Promise(resolve => setTimeout(resolve, 30)); }
          window.scrollTo(0, 0);
        });
        await expect(page.locator('main')).toBeVisible();
        if (phase !== 'before') {
          if (width <= 960 && name === 'home') {
            const menu = page.locator('.mobile-nav');
            await menu.locator('summary').click();
            await expect(menu.locator('a').first()).toBeVisible();
            await menu.locator('summary').press('Escape');
            await expect(menu).not.toHaveAttribute('open', '');
            await expect(menu.locator('summary')).toBeFocused();
            await expect(page.locator('.header-actions a[href$="wishlist"]')).toBeVisible();
          }
          if (name === 'catalog' && width <= 960) {
            const filters = page.locator('.filter-panel');
            await expect(filters).not.toHaveAttribute('open', '');
            await filters.locator('summary').click();
            await expect(filters.locator('select[name="sort"]')).toBeVisible();
            await filters.locator('summary').click();
          }
          const tiny = await page.locator('.header-actions a, .header-actions button, .quantity-control button').evaluateAll(controls => controls.filter(control => control.getBoundingClientRect().width < 44 || control.getBoundingClientRect().height < 44).length);
          expect(tiny, `${name}: touch targets`).toBe(0);
        }
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${name} ${locale} ${width}: overflow`).toBeTruthy();
        const broken = await page.locator('main img').evaluateAll(images => images.filter(image => image.complete && image.naturalWidth === 0).map(image => image.src));
        expect(broken, `${name}: broken images`).toEqual([]);
        await page.screenshot({ path: path.join(directory, `${name}-${locale}-${width}.png`), fullPage: true });
      }
    }
  }
  await page.context().clearCookies();
  for (const width of [390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('/admin');
    await expect(page).toHaveURL(/admin\/login/);
    await page.screenshot({ path: path.join(directory, `admin-login-${width}.png`), fullPage: true });
  }
  await writeFile(path.join(directory, 'diagnostics.json'), JSON.stringify({ errors, failed, responses }, null, 2));
  await testInfo.attach('browser-diagnostics', { body: JSON.stringify({ errors, failed, responses }, null, 2), contentType: 'application/json' });
  expect(errors).toEqual([]);
  expect(responses).toEqual([]);
  expect(failed.filter(request => request.url.startsWith(new URL(page.url()).origin))).toEqual([]);
});
