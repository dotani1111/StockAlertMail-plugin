import { Page, expect } from '@playwright/test';

/** プラグイン設定画面を開く */
export async function gotoConfig(page: Page): Promise<void> {
  await page.goto('/admin/plugin/stock-alert/config');
  await expect(page.locator('h2.head-title')).toContainText('在庫アラート設定');
}

/** 送信履歴画面を開く */
export async function gotoLog(page: Page): Promise<void> {
  await page.goto('/admin/plugin/stock-alert/log');
  await expect(page.locator('h2.head-title')).toContainText('送信履歴');
}

/** 閾値を変更して保存する */
export async function setThreshold(page: Page, value: number): Promise<void> {
  await gotoConfig(page);
  await page.locator('#stock_alert_config_threshold').fill(String(value));
  await page.locator('button[type="submit"].btn-ec-conversion').click();
  await expect(page.locator('.alert-success')).toBeVisible();
}

/** 通知先メールアドレスを設定して保存する */
export async function setAlertEmails(page: Page, emails: string): Promise<void> {
  await gotoConfig(page);
  await page.locator('#stock_alert_config_alertEmails').fill(emails);
  await page.locator('button[type="submit"].btn-ec-conversion').click();
  await expect(page.locator('.alert-success')).toBeVisible();
}

/** テストメール送信ボタンをクリックして確認ダイアログを承認する */
export async function sendTestMail(page: Page): Promise<void> {
  await gotoConfig(page);

  // confirm ダイアログを自動承認
  page.on('dialog', dialog => dialog.accept());

  await page.locator('button.btn-outline-primary').click();

  // リダイレクト後のフラッシュメッセージを待機
  await page.waitForURL(/\/admin\/plugin\/stock-alert\/config/);
}

/** 商品の在庫数を管理画面から変更する */
export async function setProductStock(
  page: Page,
  productId: number,
  stock: number,
): Promise<void> {
  await page.goto(`/admin/product/product/${productId}/edit`);

  // 在庫数フィールドを探して更新（規格なし商品の場合）
  const stockInput = page.locator('input[name*="stock"]').first();
  await stockInput.fill(String(stock));

  // 保存
  await page.locator('#aside_column button[type="submit"]').first().click();
  await expect(page.locator('.alert-success')).toBeVisible();
}

/** メール設定画面で在庫アラートメールテンプレートを開く */
export async function gotoMailTemplate(page: Page): Promise<void> {
  await page.goto('/admin/setting/shop/mail');

  // テンプレート選択ドロップダウンで「在庫アラートメール」を選択
  // 選択すると location.href でフルページ遷移が発生する
  const select = page.locator('#mail_template');
  const options = select.locator('option');
  const count = await options.count();

  let targetIndex = -1;
  for (let i = 0; i < count; i++) {
    const text = await options.nth(i).textContent();
    if (text?.includes('在庫アラートメール')) {
      targetIndex = i;
      break;
    }
  }

  if (targetIndex < 0) {
    throw new Error('在庫アラートメールテンプレートが見つかりません');
  }

  // selectOption でページ遷移が発生するので waitForURL で待機
  await Promise.all([
    page.waitForURL(/\/admin\/setting\/shop\/mail\/\d+/),
    select.selectOption({ index: targetIndex }),
  ]);

  // 件名フィールドが表示されるまで待機
  await expect(page.locator('#mail_mail_subject')).toBeVisible();
}
