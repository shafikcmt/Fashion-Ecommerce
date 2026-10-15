import { randomUUID, createHash } from 'node:crypto';
import { mkdir, writeFile, readFile, access } from 'node:fs/promises';
import { createServer } from 'node:net';
import { spawnSync } from 'node:child_process';
import path from 'node:path';

export function phpCommand(args, input) {
  const php = process.platform === 'win32' ? path.resolve('.tools/php/php.exe') : 'php';
  return spawnSync(php, args, { env: process.env, input, encoding: 'utf8', timeout: 120000, windowsHide: true });
}

export async function prepareBrowserEnvironment() {
  try {
    await access('bootstrap/cache/config.php');
    throw new Error('Browser checks require uncached configuration; run artisan config:clear first.');
  } catch (error) { if (error.code !== 'ENOENT') throw error; }
  if (process.env.BROWSER_REVIEW_MARKER) {
    if (!/^browser-review-[a-f0-9-]{36}$/.test(process.env.BROWSER_REVIEW_MARKER)) throw new Error('Invalid disposable database marker.');
    const expected = path.resolve('.tools/browser-review', process.env.BROWSER_REVIEW_MARKER, 'database.sqlite');
    if (path.resolve(process.env.DB_DATABASE || '') !== expected || process.env.DB_CONNECTION !== 'sqlite') throw new Error('Invalid disposable browser database.');
    return process.env.APP_URL;
  }
  const original = path.resolve('database/database.sqlite');
  try {
    await access(original);
    process.env.BROWSER_ORIGINAL_SHA = createHash('sha256').update(await readFile(original)).digest('hex');
  } catch (error) { if (error.code !== 'ENOENT') throw error; }
  const marker = 'browser-review-' + randomUUID();
  const directory = path.resolve('.tools/browser-review', marker);
  await mkdir(directory, { recursive: true });
  const database = path.join(directory, 'database.sqlite');
  await writeFile(database, '', { flag: 'wx' });
  const listener = createServer();
  await new Promise((resolve, reject) => { listener.once('error', reject); listener.listen(0, '127.0.0.1', resolve); });
  const port = listener.address().port;
  await new Promise(resolve => listener.close(resolve));
  const baseURL = 'http://127.0.0.1:' + port;
  Object.assign(process.env, {
    APP_ENV: 'local', APP_URL: baseURL, DB_CONNECTION: 'sqlite', DB_DATABASE: database,
    DB_URL: '', SESSION_DRIVER: 'database', CACHE_STORE: 'array', QUEUE_CONNECTION: 'database',
    MAIL_MAILER: 'array', MEDIA_DISK: 'public', BROWSER_REVIEW_MARKER: marker,
  });
  for (const args of [['artisan', 'migrate', '--force', '--no-interaction'], ['artisan', 'commerce:seed-demo', '--no-interaction']]) {
    const result = phpCommand(args);
    if (result.status !== 0) throw new Error('Disposable database setup failed (' + args[1] + ', exit ' + result.status + ', signal ' + result.signal + '): ' + (result.error?.message || result.stderr || result.stdout));
  }
  const markerResult = phpCommand(['-r', '$pdo = new PDO("sqlite:".getenv("DB_DATABASE")); $statement = $pdo->prepare("INSERT INTO pages (title,slug,body,published,created_at,updated_at) VALUES (?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)"); $statement->execute(["Disposable review environment",getenv("BROWSER_REVIEW_MARKER"),getenv("BROWSER_REVIEW_MARKER")]);']);
  if (markerResult.status !== 0) throw new Error('Unable to create the disposable environment marker.');
  await writeFile('.tools/browser-review/latest.json', JSON.stringify({ directory, database, baseURL, marker }, null, 2));
  return baseURL;
}

export default async function verifyOriginalDatabase() {
  if (!process.env.BROWSER_ORIGINAL_SHA) return;
  const actual = createHash('sha256').update(await readFile(path.resolve('database/database.sqlite'))).digest('hex');
  if (actual !== process.env.BROWSER_ORIGINAL_SHA) throw new Error('Original development database changed during browser tests.');
  console.log('Original development database byte fingerprint unchanged.');
}
