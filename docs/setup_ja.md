# StudyTestApp セットアップガイド

## 1. 前提

- PHP 8.2+
- Composer
- Node.js 20 系推奨
- npm

## 2. 初回セットアップ

1. 依存関係をインストール

```bash
composer install
npm install
```

2. 環境ファイルとキーを作成

```bash
cp .env.example .env
php artisan key:generate
```

3. SQLite を使う場合は DB ファイルを作成

```bash
touch database/database.sqlite
```

4. マイグレーション実行

```bash
php artisan migrate
```

5. 必要なら管理者ユーザーを作成

```bash
php artisan admin:create-user "Admin User" "admin@example.com" --password=password123 --verified
```

6. 開発サーバー起動

```bash
composer run dev
```

`composer run dev` は以下を同時起動します。

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0`
- `npm run dev`

## 3. テストと静的確認

```bash
composer test
find app config routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

## 4. よくあるトラブル

- `npm run dev` や `npm run build` で `vite: Permission denied` が出る

```bash
rm -rf node_modules
npm install
```

- `php artisan migrate` で SQLite エラーが出る

```bash
touch database/database.sqlite
php artisan migrate
```

- `storage` / `bootstrap/cache` の権限エラー

```bash
chmod -R ug+rwx storage bootstrap/cache
```

## 5. 2回目以降の起動

通常は以下のみで起動可能です。

```bash
composer run dev
```

依存関係を更新した場合は、必要に応じて `composer install` と `npm install` を再実行してください。
