import { test as setup, expect } from '@playwright/test';

setup('管理画面にログインしてセッションを保存', async ({ page }) => {
  await page.goto('/admin/login');
  await page.locator('#login_id').fill('admin');
  await page.locator('#password').fill('password');
  await page.locator('button[type="submit"]').click();

  // ダッシュボードに遷移するまで待機
  await expect(page).toHaveURL(/\/admin\/?$/);

  await page.context().storageState({ path: './auth.json' });
});
