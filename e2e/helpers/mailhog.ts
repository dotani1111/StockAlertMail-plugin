const MAILHOG_API = process.env.MAILHOG_API || 'http://localhost:8025/api';

export interface MailhogMessage {
  ID: string;
  Content: {
    Headers: {
      Subject: string[];
      To: string[];
      From: string[];
      'Content-Transfer-Encoding'?: string[];
    };
    Body: string;
  };
}

interface MailhogResponse {
  total: number;
  count: number;
  items: MailhogMessage[];
}

/** 全メールを削除する */
export async function deleteAllMessages(): Promise<void> {
  await fetch(`${MAILHOG_API}/v1/messages`, { method: 'DELETE' });
}

/** 全メールを取得する（新しい順） */
export async function getMessages(): Promise<MailhogMessage[]> {
  const res = await fetch(`${MAILHOG_API}/v2/messages`);
  const data: MailhogResponse = await res.json();
  return data.items;
}

/** 条件を満たすメールが届くまで待機する（最大 timeout ミリ秒） */
export async function waitForMessage(
  predicate: (msg: MailhogMessage) => boolean,
  timeout = 10_000,
  interval = 500,
): Promise<MailhogMessage> {
  const start = Date.now();
  while (Date.now() - start < timeout) {
    const messages = await getMessages();
    const found = messages.find(predicate);
    if (found) return found;
    await new Promise(r => setTimeout(r, interval));
  }
  throw new Error(`メールが ${timeout}ms 以内に届きませんでした`);
}

/** メールの件名を取得する（MIME エンコード済みの場合はデコード） */
export function getSubject(msg: MailhogMessage): string {
  const raw = msg.Content.Headers.Subject?.[0] ?? '';
  return decodeMimeEncoded(raw);
}

/** メールの本文を取得する（quoted-printable UTF-8 をデコード） */
export function getBody(msg: MailhogMessage): string {
  return decodeQuotedPrintableUtf8(msg.Content.Body);
}

/** メールの To を取得する */
export function getTo(msg: MailhogMessage): string[] {
  return msg.Content.Headers.To ?? [];
}

/** MIME encoded-word (=?utf-8?Q?...?= / =?utf-8?B?...?=) をデコード */
function decodeMimeEncoded(str: string): string {
  // RFC 2047: 隣接する encoded-word 間の空白は無視する
  const collapsed = str.replace(/\?=\s+=\?/g, '?==?');
  return collapsed.replace(/=\?([^?]+)\?([BQ])\?([^?]*)\?=/gi, (_, _charset, encoding, encoded) => {
    if (encoding.toUpperCase() === 'B') {
      return Buffer.from(encoded, 'base64').toString('utf-8');
    }
    // Q encoding
    const qpStr = encoded.replace(/_/g, ' ');
    return decodeQuotedPrintableUtf8(qpStr);
  });
}

/** Quoted-Printable でエンコードされた UTF-8 バイト列をデコード */
function decodeQuotedPrintableUtf8(str: string): string {
  // ソフト改行を除去
  const joined = str.replace(/=\r?\n/g, '');

  // =XX をバイトに変換してから UTF-8 としてデコード
  const bytes: number[] = [];
  for (let i = 0; i < joined.length; i++) {
    if (joined[i] === '=' && i + 2 < joined.length) {
      const hex = joined.substring(i + 1, i + 3);
      if (/^[0-9A-Fa-f]{2}$/.test(hex)) {
        bytes.push(parseInt(hex, 16));
        i += 2;
        continue;
      }
    }
    bytes.push(joined.charCodeAt(i));
  }

  return Buffer.from(bytes).toString('utf-8');
}
