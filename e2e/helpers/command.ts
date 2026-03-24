import { execSync } from 'child_process';
import path from 'path';

const ECCUBE_ROOT = process.env.ECCUBE_ROOT
  || path.resolve(__dirname, '../../../../../..');

/** EC-CUBE の bin/console コマンドを実行する */
export function execConsole(command: string): string {
  return execSync(`php bin/console ${command}`, {
    cwd: ECCUBE_ROOT,
    encoding: 'utf-8',
    timeout: 30_000,
    env: { ...process.env },
  }).trim();
}

/** 在庫アラートコマンドを実行する */
export function execStockAlert(): { exitCode: number; output: string } {
  try {
    const output = execConsole('eccube:plugin:stock-alert-mail');
    return { exitCode: 0, output };
  } catch (e: any) {
    return {
      exitCode: e.status ?? 1,
      output: e.stdout?.toString() ?? e.message,
    };
  }
}
