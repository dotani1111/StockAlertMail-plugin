import { test, expect } from '@playwright/test';

test.describe('ナビゲーション', () => {
  test('TC-1.1: サイドバーに在庫アラートメニューが表示される', async ({ page }) => {
    await page.goto('/admin');

    // サイドバーに「在庫アラート」が存在する
    const sidebar = page.locator('#side_menu, .c-mainNavArea');
    await expect(sidebar).toContainText('在庫アラート');
  });

  test('TC-1.2: サブメニュー「設定」から設定画面に遷移する', async ({ page }) => {
    await page.goto('/admin');

    // 「在庫アラート」メニューを開く
    const menuItem = page.locator('text=在庫アラート').first();
    await menuItem.click();

    // 「設定」サブメニューをクリック
    const configLink = page.locator('a[href*="stock-alert/config"]').first();
    await configLink.click();

    await expect(page).toHaveURL(/\/admin\/plugin\/stock-alert\/config/);
    await expect(page.locator('h2.head-title')).toContainText('在庫アラート設定');
  });

  test('TC-1.3: サブメニュー「送信履歴」から送信履歴画面に遷移する', async ({ page }) => {
    await page.goto('/admin');

    const menuItem = page.locator('text=在庫アラート').first();
    await menuItem.click();

    const logLink = page.locator('a[href*="stock-alert/log"]').first();
    await logLink.click();

    await expect(page).toHaveURL(/\/admin\/plugin\/stock-alert\/log/);
    await expect(page.locator('h2.head-title')).toContainText('送信履歴');
  });
});
