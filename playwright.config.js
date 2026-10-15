import { existsSync } from 'node:fs';
import { defineConfig, devices } from '@playwright/test';
import { prepareBrowserEnvironment } from './tests/Browser/environment.mjs';
process.env.PLAYWRIGHT_NO_COPY_PROMPT = '1';
const baseURL = await prepareBrowserEnvironment();
const chromePath = process.env.PLAYWRIGHT_CHROME_PATH || (process.platform === 'win32' && existsSync('C:/Program Files/Google/Chrome/Application/chrome.exe') ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : undefined);
const port = new URL(baseURL).port || '8000';
export default defineConfig({
  testDir: './tests/Browser',
  timeout: 90000,
  globalTeardown: './tests/Browser/environment.mjs',
  fullyParallel: false,
  expect: { timeout: 15000 },
  workers: 1,
  retries: 0,
  use: {
    baseURL,
    screenshot: 'off',
    trace: 'off',
    launchOptions: chromePath ? { executablePath: chromePath } : {},
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['iPhone 13'], defaultBrowserType: 'chromium' } },
  ],
  webServer: {
    command: `${process.platform === 'win32' ? '.tools\\php\\php.exe' : 'php'} artisan serve --no-reload --host=127.0.0.1 --port=${port}`,
    url: baseURL,
    reuseExistingServer: false,
    timeout: 30000,
  },
});
