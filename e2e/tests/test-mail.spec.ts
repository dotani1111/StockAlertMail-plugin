import { test, expect } from '@playwright/test';
import { gotoConfig, sendTestMail, setAlertEmails } from '../helpers/pages';
import {
  deleteAllMessages,
  waitForMessage,
  getSubject,
  getBody,
  getTo,
} from '../helpers/mailhog';

test.describe('テストメール送信', () => {
  test.beforeEach(async () => {
    await deleteAllMessages();
  });

  test('TC-3.1: テストメールが正常に送信される', async ({ page }) => {
    await sendTestMail(page);

    // 成功メッセージ
    await expect(page.locator('.alert-success')).toContainText('テストメールを');
    await expect(page.locator('.alert-success')).toContainText('送信しました');

    // MailHog でメールを確認
    const msg = await waitForMessage(m => getSubject(m).includes('[TEST]'));

    const subject = getSubject(msg);
    expect(subject).toMatch(/\[TEST\] \[.+\] .+/);

    const body = getBody(msg);
    expect(body).toContain('管理者様');
    expect(body).toContain('サンプル商品A');
    expect(body).toContain('サンプル商品B');
  });

  test('TC-3.2: 確認ダイアログでキャンセルするとメールが送信されない', async ({ page }) => {
    await gotoConfig(page);

    // confirm ダイアログをキャンセル
    page.on('dialog', dialog => dialog.dismiss());

    await page.locator('button.btn-outline-primary').click();

    // 少し待ってもメールが届かないことを確認
    await page.waitForTimeout(2000);
    try {
      await waitForMessage(() => true, 1000);
      // ここに到達したらメールが届いてしまっている
      expect(true).toBe(false);
    } catch {
      // メールが届かない = 期待通り
    }
  });

  test('TC-3.3: カスタム通知先にテストメールが送信される', async ({ page }) => {
    // 通知先を設定
    await setAlertEmails(page, 'test-e2e@example.com');

    await sendTestMail(page);

    // 成功メッセージに通知先が含まれる
    await expect(page.locator('.alert-success')).toContainText('test-e2e@example.com');

    // MailHog で宛先を確認
    const msg = await waitForMessage(m => getSubject(m).includes('[TEST]'));
    const to = getTo(msg);
    expect(to.join(',')).toContain('test-e2e@example.com');

    // 通知先を元に戻す
    await setAlertEmails(page, '');
  });
});
