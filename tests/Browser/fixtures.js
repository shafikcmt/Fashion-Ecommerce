import { test as base, expect } from '@playwright/test';
process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';
export const test = base.extend({
  page: async ({ page }, use) => {
    if (!process.env.BROWSER_REVIEW_MARKER) throw new Error('Missing disposable database marker.');
    await page.goto('/pages/' + process.env.BROWSER_REVIEW_MARKER);
    await expect(page.locator('main')).toContainText(process.env.BROWSER_REVIEW_MARKER);
    await use(page);
  },
});
export { expect };
