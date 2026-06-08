# StudyTestApp

StudyTestApp は、Laravel 12 ベースの学習用テストアプリです。問題・試験の管理、受験、要復習フラグ、ポイント付与、教師の生徒進捗確認、管理者のユーザー運用を一体で扱います。

## 主な機能

- 試験管理: 一覧、作成、編集、削除
- 問題管理: 一覧、作成、編集、削除、CSV インポート、テンプレートダウンロード
- 受験機能: 通常モード、フラグ付きモード、指定数ランダム出題モード
- 採点機能: 複数選択の完全一致採点、解説表示、試験結果保存
- ポイント機能: 問題難易度に応じた加点、全問正解ボーナス
- フラグ機能: 問題ごとの要復習フラグ切り替え
- 教師機能: 担当生徒の正答率とポイントの確認、フィードバックコメント
- 教師機能: 全生徒ポイントの手動リセット、自動リセット間隔の設定
- 管理者機能: ユーザー個別登録、CSV 一括登録、教師生徒紐付け、正答率一覧

## ロール

- student: 受験、フラグ、プロフィール更新
- teacher: student 権限に加え、問題管理、試験作成、生徒進捗管理
- admin: teacher 権限に加え、試験編集・削除、ユーザー管理、紐付け管理

補足:

- 新規登録ユーザーは既定で student
- teacher の問題編集・削除は自分が作成した問題のみ
- 試験の編集・削除は admin のみ

## 技術スタック

- PHP 8.2+
- Laravel 12
- Blade
- Tailwind CSS
- Vite
- SQLite（既定）

## クイックスタート

1. 依存関係をインストール

```bash
composer install
npm install
```

2. 環境ファイルとアプリキーを準備

```bash
cp .env.example .env
php artisan key:generate
```

3. DB ファイルを準備してマイグレーション

```bash
touch database/database.sqlite
php artisan migrate
```

4. 必要なら管理者を作成

```bash
php artisan admin:create-user "Admin User" "admin@example.com" --password=password123 --verified
```

5. 開発サーバー起動

```bash
composer run dev
```

## 主なルート

- 公開: `/`, `/test`
- 認証必須: `/dashboard`, `/profile`, `/quiz`, `/quiz/{exam}`, `/quiz/{exam}/resume`, `/quiz/{exam}/next`
- teacher 以上: `/questions`, `/questions/import`, `/exams`, `/teacher/students/progress`, `/teacher/students/points/reset`
- admin のみ: `/admin/users`, `/admin/users/accuracy`, `/admin/teacher-students`

## 運用メモ

- `admin:create-user` は `routes/console.php` の Artisan クロージャコマンド
- 受験完了時には `exam_results` へ結果を保存
- ポイント自動リセットは教師画面アクセス時に期限判定して実行
- Dockerfile と docker-compose.yml は同梱済み（README はローカル実行手順を優先）

## テスト

```bash
composer test
```

## ドキュメント

- [docs/application_overview_ja.md](docs/application_overview_ja.md)
- [docs/setup_ja.md](docs/setup_ja.md)
