import { test, expect } from '@playwright/test';
import { gotoConfig, setThreshold, setAlertEmails } from '../helpers/pages';

test.describe('設定画面', () => {
  test('TC-2.1: 設定画面が正常に表示される', async ({ page }) => {
    await gotoConfig(page);

    // アラート設定セクション（card-header 内の span で完全一致）
    await expect(page.locator('.card-header span').filter({ hasText: /^アラート設定$/ })).toBeVisible();
    await expect(page.locator('#stock_alert_config_threshold')).toBeVisible();
    await expect(page.locator('#stock_alert_config_alertEmails')).toBeVisible();

    // メールテンプレートセクション
    await expect(page.locator('.card-header span').filter({ hasText: /^メールテンプレート$/ })).toBeVisible();
    await expect(page.locator('.card-body a[href*="setting/shop/mail"]')).toBeVisible();

    // cron設定例セクション
    await expect(page.locator('.card-header span').filter({ hasText: /^cron 設定例$/ })).toBeVisible();
    await expect(page.locator('code')).toContainText('eccube:plugin:stock-alert-mail');

    // ボタン
    await expect(page.locator('button.btn-outline-primary')).toBeVisible();
    await expect(page.locator('button.btn-ec-conversion')).toBeVisible();
  });

  test('TC-2.2: 閾値を変更して保存できる', async ({ page }) => {
    await setThreshold(page, 10);

    // リロードして値が保持されていることを確認
    await page.reload();
    const value = await page.locator('#stock_alert_config_threshold').inputValue();
    expect(value).toBe('10');

    // 元に戻す
    await setThreshold(page, 5);
  });

  test('TC-2.3: 閾値のバリデーション — 空欄', async ({ page }) => {
    await gotoConfig(page);
    await page.locator('#stock_alert_config_threshold').fill('');
    await page.locator('button[type="submit"].btn-ec-conversion').click();

    // バリデーションエラーが表示される（成功メッセージが出ない）
    const hasSuccess = await page.locator('.alert-success').isVisible().catch(() => false);
    expect(hasSuccess).toBeFalsy();
  });

  test('TC-2.4: 通知先メールアドレスを設定できる', async ({ page }) => {
    await setAlertEmails(page, 'test1@example.com, test2@example.com');

    // リロードして値が保持されていることを確認
    await page.reload();
    const value = await page.locator('#stock_alert_config_alertEmails').inputValue();
    expect(value).toContain('test1@example.com');
    expect(value).toContain('test2@example.com');

    // 元に戻す（空欄＝デフォルト使用）
    await setAlertEmails(page, '');
  });

  test('TC-2.5: 通知先が空欄の場合、ヘルプテキストに店舗メールが表示される', async ({ page }) => {
    await gotoConfig(page);

    // ヘルプテキストに店舗メールアドレスが含まれること
    const helpText = page.locator('#stock_alert_config_alertEmails')
      .locator('..').locator('.form-text');
    await expect(helpText).toContainText('空欄の場合は店舗設定のメールアドレスを使用します');
  });

  test('TC-2.6: メール設定リンクが正しく遷移する', async ({ page }) => {
    await gotoConfig(page);

    const link = page.locator('.card-body a[href*="setting/shop/mail"]');
    await expect(link).toBeVisible();

    await link.click();
    await expect(page).toHaveURL(/\/admin\/setting\/shop\/mail/);
  });
});
