# StudyTestApp アプリケーション解説

## 1. 概要

StudyTestApp は、試験と問題の管理、受験、採点、学習進捗確認を行う Laravel 12 アプリです。現在の実装は以下の 3 系統で構成されています。

- 出題管理
- 学習者向け受験機能
- 教師・管理者向け運用機能

## 2. 技術構成

- バックエンド: Laravel 12 / PHP 8.2+
- フロントエンド: Blade + Tailwind CSS + Vite
- 認証: Laravel Breeze
- ORM: Eloquent
- 既定DB: SQLite

主要ルートは `routes/web.php`、認証ルートは `routes/auth.php`、管理者作成コマンドは `routes/console.php` に定義されています。

## 3. ロールと権限

ロールは `users.role` で管理され、`student`, `teacher`, `admin` の 3 種類です。

- student
  - 受験
  - フラグ操作
  - プロフィール更新
- teacher
  - student 権限
  - 問題管理（自作問題の編集・削除）
  - 問題CSVインポート
  - 試験作成
  - 担当生徒の進捗確認、コメント、ポイント管理
- admin
  - teacher 権限
  - 試験編集・削除
  - ユーザー管理
  - 教師生徒紐付け管理

補足:

- 新規登録ユーザーは既定で student
- 試験編集・削除は `ExamController` 側で admin のみに制限

## 4. 機能別の実装状況

### 4-1. 試験管理

- 実装: `app/Http/Controllers/ExamController.php`
- 主機能: 一覧、作成、編集、削除
- ポイント:
  - 作成は teacher 以上
  - 編集・削除は admin のみ

### 4-2. 問題管理

- 実装: `app/Http/Controllers/QuestionController.php`
- 主機能: 一覧、作成、編集、削除、CSVインポート、テンプレートダウンロード
- ポイント:
  - teacher は自分が作成した問題のみ編集・削除可能
  - フラグ付き問題での絞り込みに対応

### 4-3. 受験機能

- 実装: `app/Http/Controllers/QuizController.php`
- 出題モード:
  - 通常モード（試験内の問題から出題）
  - フラグ付きモード（自分がフラグした問題のみ）
  - 指定数ランダム出題モード
- 採点:
  - 複数選択の完全一致採点
  - 結果画面で全体解説と選択肢解説を表示
- セッション管理:
  - `quiz_state` で進行状態を保持
  - 指定数ランダム出題中は画面遷移をミドルウェアで制限
- 成績保存:
  - 出題完了時に `exam_results` へ保存

### 4-4. ポイント機能

- 問題難易度別ポイント（easy/normal/expert）を正解時に付与
- 全問正解時にボーナスポイントを付与
- 学生ポイントは `users.total_points` で管理

### 4-5. フラグ機能

- 実装: `app/Http/Controllers/FlagController.php`
- `flagged_questions` 中間テーブルでユーザーと問題を多対多管理

### 4-6. 教師の生徒進捗管理

- 実装: `app/Http/Controllers/Teacher/StudentProgressController.php`
- 主機能:
  - 担当生徒の正答率・ポイント確認
  - フィードバックコメント保存
  - 全生徒ポイントの手動リセット
  - ポイント自動リセット間隔の設定

### 4-7. 管理者のユーザー管理

- 実装: `app/Http/Controllers/Admin/UserManagementController.php`
- 主機能:
  - ユーザー一覧
  - teacher/student の個別作成
  - CSV 一括作成
  - 正答率一覧
  - 教師生徒紐付け追加・解除

## 5. 主なデータモデル

- `Exam` 1:N `Question`
- `Question` 1:N `Option`
- `User` N:N `Question`（`flagged_questions`）
- `User(teacher)` N:N `User(student)`（`teacher_student`）
- `ExamResult` は `user_id`, `exam_id` に紐づく受験結果
- `PointResetSetting` はポイントリセット設定を保持

## 6. 主要ルート（抜粋）

- 公開
  - `GET /`
  - `GET /test`
- 認証必須
  - `GET /dashboard`
  - `GET|PATCH|DELETE /profile`
  - `GET /quiz`
  - `GET /quiz/{exam}`
  - `GET /quiz/{exam}/resume`
  - `POST /quiz/{exam}/next`
  - `POST /quiz/{exam}/answer`
  - `POST /questions/{question}/toggle-flag`
- teacher 以上
  - `questions` リソース
  - `questions/import`
  - `exams` リソース
  - `GET /teacher/students/progress`
  - `POST /teacher/students/points/reset`
  - `PATCH /teacher/students/points/reset-interval`
- admin
  - `admin/users`
  - `admin/users/import`
  - `admin/users/accuracy`
  - `admin/teacher-students`

## 7. 運用メモ

- CLI の PHP は 8.2 以上が必須
- `admin:create-user` は `routes/console.php` の Artisan クロージャとして定義
- 正答率画面は `exam_results` 集計を参照し、受験完了時にデータが蓄積される
- セットアップ手順は `docs/setup_ja.md` を参照

## 8. 今後の改善候補

- 受験履歴の詳細画面（時系列）追加
- 正答率の期間フィルタ、試験別比較の追加
- Docker 構成の起動手順ドキュメント整備
