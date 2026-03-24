import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30_000,
  expect: { timeout: 5_000 },
  fullyParallel: false,
  retries: 0,
  workers: 1,
  reporter: 'html',
  use: {
    baseURL: process.env.ECCUBE_URL || 'http://127.0.0.1:8000',
    screenshot: 'only-on-failure',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'setup',
      testMatch: /global-setup\.ts/,
    },
    {
      name: 'e2e',
      dependencies: ['setup'],
      testMatch: /.*\.spec\.ts/,
      use: { storageState: './auth.json' },
    },
  ],
});
