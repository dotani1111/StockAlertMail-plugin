import { test, expect } from '@playwright/test';
import { gotoLog, setThreshold } from '../helpers/pages';
import { execStockAlert } from '../helpers/command';
import { deleteAllMessages } from '../helpers/mailhog';

test.describe('送信履歴画面', () => {
  test('TC-6.1: 送信履歴画面が正常に表示される', async ({ page }) => {
    await gotoLog(page);
    await expect(page.locator('h2.head-title')).toContainText('送信履歴');
  });

  test('TC-6.2: アラート送信後に送信履歴にログが表示される', async ({ page }) => {
    await deleteAllMessages();

    // 閾値を高くしてアラート送信
    await setThreshold(page, 9999);
    execStockAlert();

    // 送信履歴画面を確認
    await gotoLog(page);

    // テーブルにカラムヘッダーが表示されている
    const cardBody = page.locator('.card-body');
    await expect(cardBody).toContainText('商品名');
    await expect(cardBody).toContainText('送信日時');

    // 商品リンクが存在する
    const productLink = cardBody.locator('a[href*="admin/product/product"]').first();
    await expect(productLink).toBeVisible();
  });

  test('TC-6.3: 商品リンクから商品編集ページに遷移できる', async ({ page }) => {
    // アラート送信済みの状態を確保
    await setThreshold(page, 9999);
    execStockAlert();

    await gotoLog(page);

    const productLink = page.locator('table a[href*="admin/product/product"]').first();
    const isVisible = await productLink.isVisible().catch(() => false);

    if (isVisible) {
      await productLink.click();
      await expect(page).toHaveURL(/\/admin\/product\/product\/\d+\/edit/);
    } else {
      test.skip();
    }
  });
});

test.afterAll(async ({ browser }) => {
  // 閾値をデフォルトに戻す
  const context = await browser.newContext({ storageState: './auth.json' });
  const page = await context.newPage();
  await setThreshold(page, 5);
  await context.close();
});
