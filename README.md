# StudyTestApp

StudyTestApp は、Laravel 12 をベースにした学習用テストアプリです。試験ごとの問題管理、受験、要復習フラグ、教師による担当生徒の進捗確認、管理者によるユーザー管理をまとめて扱います。

詳細な機能整理は [docs/application_overview_ja.md](docs/application_overview_ja.md) を参照してください。

## 主な機能

- 試験管理: 試験一覧、作成、編集、削除
- 試験管理の編集と削除は管理者のみ実行可能
- 問題管理: 問題の作成、一覧、編集、削除
- 問題管理では CSV インポートとテンプレートダウンロードに対応
- 教師は自分が作成した問題のみ編集・削除可能
- 受験機能: 試験を選んでランダムに 1 問出題
- 受験機能は複数選択対応の完全一致採点
- 結果画面で問題全体の解説と選択肢ごとの解説を表示
- 要復習フラグ: 問題ごとのフラグ付けと解除
- 問題一覧でフラグ付きのみ抽出可能
- 教師機能: 担当生徒の正答率集計の閲覧とフィードバックコメント保存
- 管理者機能: 教師・生徒ユーザーの個別登録、CSV 一括登録、教師と生徒の紐付け管理、全ユーザーの正答率一覧

## ロール

- admin: 全機能にアクセス可能
- admin は試験の編集・削除、ユーザー管理、教師生徒紐付け管理を担当
- teacher: 問題管理、CSV インポート、受験機能を利用可能
- teacher は自分が作成した問題だけ編集・削除可能
- teacher は担当生徒の進捗とフィードバックを扱える
- student: 受験、フラグ、プロフィール関連を利用可能
- 新規登録ユーザーは既定で student

## 技術スタック

- PHP 8.2 以上
- Laravel 12
- Blade
- Tailwind CSS
- Vite
- Eloquent ORM

## セットアップ

1. 依存関係をインストールします。

```bash
composer install
npm install
```

1. 環境設定を作成します。

```bash
copy .env.example .env
php artisan key:generate
```

1. データベースを設定してマイグレーションを実行します。

```bash
php artisan migrate
```

1. 管理者が必要なら作成します。

```bash
php artisan admin:create-user "Admin User" "admin@example.com" --password=password123 --verified
```

1. 開発サーバーを起動します。

```bash
composer run dev
```

## 主なルート構成

- 認証不要: /
- 認証必須: /dashboard, /profile, /quiz, /quiz/{exam}
- teacher 以上: /questions, /questions/import, /exams, /teacher/students/progress
- admin のみ: /admin/users, /admin/users/accuracy, /admin/teacher-students

## 運用メモ

- CLI で PHP 8.2 未満を使うと Composer の platform check により artisan 実行が失敗します。
- admin:create-user は [routes/console.php](routes/console.php) に定義されています。
- 試験結果集計画面は exam_results テーブルを参照しますが、現状の受験処理では exam_results への保存を行っていません。そのため、集計画面は別途データ投入しない限り空または 0 件ベースになります。
- Dockerfile と docker-compose.yml は存在しますが、この README ではローカル開発手順を正としています。

## テスト

```bash
composer test
```

## ドキュメント

- [docs/application_overview_ja.md](docs/application_overview_ja.md)
