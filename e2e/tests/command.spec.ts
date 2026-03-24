import { test, expect } from '@playwright/test';
import { execStockAlert } from '../helpers/command';
import { setThreshold } from '../helpers/pages';
import {
  deleteAllMessages,
  waitForMessage,
  getMessages,
  getSubject,
  getBody,
} from '../helpers/mailhog';

test.describe('コマンド実行', () => {
  test.beforeEach(async () => {
    await deleteAllMessages();
  });

  test('TC-5.1: アラート対象商品ありでメール送信される', async ({ page }) => {
    // 閾値を高く設定して既存商品をアラート対象にする
    await setThreshold(page, 9999);

    const result = execStockAlert();
    expect(result.exitCode).toBe(0);
    expect(result.output).toContain('送信しました');

    // MailHog でメールを確認（[TEST] プレフィックスがないアラートメール）
    const msg = await waitForMessage(
      m => !getSubject(m).includes('[TEST]') && getSubject(m).startsWith('['),
      30_000,
    );

    const subject = getSubject(msg);
    expect(subject).toMatch(/\[.+\] .+/);

    const body = getBody(msg);
    expect(body).toContain('管理者様');
    expect(body).toContain('在庫');
  });

  test('TC-5.2: アラート対象商品なしでメール送信されない', async ({ page }) => {
    // 閾値を-1にして対象商品をなくす
    await setThreshold(page, 0);

    const result = execStockAlert();
    expect(result.exitCode).toBe(0);
    expect(result.output).toContain('新規の在庫アラート対象商品はありません');

    // メールが送信されていないことを確認
    await new Promise(r => setTimeout(r, 2000));
    const messages = await getMessages();
    expect(messages.length).toBe(0);
  });

  test('TC-5.3: 同じ商品に対して重複送信されない', async ({ page }) => {
    // 閾値を高く設定
    await setThreshold(page, 9999);

    // 1回目実行
    const result1 = execStockAlert();
    expect(result1.exitCode).toBe(0);

    await deleteAllMessages();

    // 2回目実行 — 同じ商品は送信済みなのでメールなし
    const result2 = execStockAlert();
    expect(result2.exitCode).toBe(0);
    expect(result2.output).toContain('新規の在庫アラート対象商品はありません');

    await new Promise(r => setTimeout(r, 2000));
    const messages = await getMessages();
    expect(messages.length).toBe(0);
  });

  test('TC-5.4: 在庫回復後に再アラートされる', async ({ page }) => {
    // 1. 閾値を高くしてアラート送信
    await setThreshold(page, 9999);
    execStockAlert();
    await deleteAllMessages();

    // 2. 閾値を0にして在庫回復扱い→ログリセット
    await setThreshold(page, 0);
    execStockAlert();

    // 3. 再度閾値を高くして再アラート
    await setThreshold(page, 9999);
    const result = execStockAlert();
    expect(result.exitCode).toBe(0);
    expect(result.output).toContain('送信しました');

    const msg = await waitForMessage(
      m => !getSubject(m).includes('[TEST]') && getSubject(m).startsWith('['),
      30_000,
    );
    expect(getSubject(msg)).toBeTruthy();
  });
});

test.afterAll(async ({ browser }) => {
  // 閾値をデフォルトに戻す
  const context = await browser.newContext({ storageState: './auth.json' });
  const page = await context.newPage();
  await setThreshold(page, 5);
  await context.close();
});
