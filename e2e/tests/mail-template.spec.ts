import { test, expect } from '@playwright/test';
import { gotoMailTemplate, sendTestMail } from '../helpers/pages';
import {
  deleteAllMessages,
  waitForMessage,
  getSubject,
} from '../helpers/mailhog';

test.describe('メールテンプレート', () => {
  test.beforeEach(async () => {
    await deleteAllMessages();
  });

  test('TC-4.1: メールテンプレートの件名を編集してテスト送信', async ({ page }) => {
    // メール設定画面で件名を変更
    await gotoMailTemplate(page);

    const subjectInput = page.locator('#mail_mail_subject');
    const originalSubject = await subjectInput.inputValue();

    await subjectInput.fill('カスタム在庫通知');

    // フォーム送信後にリダイレクトされるので waitForURL で待機
    await Promise.all([
      page.waitForURL(/\/admin\/setting\/shop\/mail\/\d+/),
      page.locator('button.btn-ec-conversion[type="submit"]').click(),
    ]);
    await expect(page.locator('.alert-success')).toBeVisible();

    // テストメール送信
    await sendTestMail(page);

    // MailHog で件名を確認
    const msg = await waitForMessage(m => getSubject(m).includes('[TEST]'));
    const subject = getSubject(msg);
    expect(subject).toContain('カスタム在庫通知');

    // 件名を元に戻す
    await gotoMailTemplate(page);
    await page.locator('#mail_mail_subject').fill(originalSubject);
    await Promise.all([
      page.waitForURL(/\/admin\/setting\/shop\/mail\/\d+/),
      page.locator('button.btn-ec-conversion[type="submit"]').click(),
    ]);
  });

  test('TC-4.2: 件名を空白にすると保存時にバリデーションエラーになる', async ({ page }) => {
    // メール設定画面で件名を空白に変更
    await gotoMailTemplate(page);

    const subjectInput = page.locator('#mail_mail_subject');
    await subjectInput.fill('');

    // 送信ボタンをクリック（HTML5 required バリデーションで送信されない場合もある）
    await page.locator('button.btn-ec-conversion[type="submit"]').click();

    // 成功メッセージが表示されないことを確認
    await page.waitForTimeout(1000);
    await expect(page.locator('.alert-success')).not.toBeVisible();
  });
});
