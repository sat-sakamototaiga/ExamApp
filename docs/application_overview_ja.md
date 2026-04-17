# StudyTestApp アプリケーション解説

## 1. 概要

StudyTestApp は、Laravel ベースの学習用テストアプリです。現在の実装は次の 3 系統で構成されています。

- 出題管理
  - 試験の作成と問題の管理
  - 問題 CSV インポート
- 学習者向け機能
  - 試験選択、ランダム出題、即時採点、解説表示
  - 要復習フラグ
- 運用管理
  - ロール管理
  - 教師と生徒の紐付け
  - 教師コメント
  - ユーザー管理と正答率一覧

## 2. 技術構成

- バックエンド: Laravel 12 / PHP 8.2+
- フロント: Blade + Tailwind CSS + Vite
- 認証: Laravel Breeze ベース
- データアクセス: Eloquent ORM

主要ルートは [routes/web.php](routes/web.php) に定義されています。認証関連は [routes/auth.php](routes/auth.php)、管理者作成コマンドは [routes/console.php](routes/console.php) にあります。

## 3. 権限制御

ロールは [app/Models/User.php](app/Models/User.php) の定数で定義されており、users.role に保存されます。

- student
- teacher
- admin

権限判定は [app/Http/Middleware/EnsureRoleHierarchy.php](app/Http/Middleware/EnsureRoleHierarchy.php) の role.hierarchy ミドルウェアと、各コントローラ内の追加判定で行われます。

現状の実装上の権限は次の通りです。

- student
  - 受験
  - フラグ付け
  - プロフィール変更
- teacher
  - 問題一覧、作成、編集、削除
  - 問題 CSV インポート
  - 試験一覧、試験作成
  - 担当生徒の進捗閲覧とフィードバック登録
- admin
  - teacher の全機能
  - 試験編集、試験削除
  - ユーザー管理
  - 正答率一覧
  - 教師生徒の紐付け管理

補足:

- 問題編集と削除は、teacher の場合は自分が作成した問題に限定されます。
- 試験の編集と削除は role.hierarchy:teacher に含まれていますが、[app/Http/Controllers/ExamController.php](app/Http/Controllers/ExamController.php) 内で管理者のみ許可されています。
- 新規登録ユーザーは role 未指定で作成され、DB の既定値により student になります。

## 4. 機能別の現状

### 4-1. 認証と基本導線

- / は welcome 画面を表示
- /dashboard は auth + verified で保護
- login、register、password reset、email verification は Breeze 標準構成

### 4-2. 試験管理

実装箇所:

- [app/Http/Controllers/ExamController.php](app/Http/Controllers/ExamController.php)
- [app/Models/Exam.php](app/Models/Exam.php)

機能:

- 一覧表示
- 作成
- 編集
- 削除

仕様:

- 試験名は必須かつユニーク
- description は任意
- 問題は exams に属する 1 対多

注意点:

- 作成は teacher 以上で実行可能
- 編集と削除は管理者のみ
- コードコメントでは questions.exam_id の cascade を前提にしていますが、実際の削除挙動はマイグレーション定義に依存します

### 4-3. 問題管理

実装箇所:

- [app/Http/Controllers/QuestionController.php](app/Http/Controllers/QuestionController.php)
- [app/Models/Question.php](app/Models/Question.php)
- [app/Models/Option.php](app/Models/Option.php)

機能:

- 問題一覧
- 問題作成
- 問題編集
- 問題削除
- CSV インポート
- テンプレートダウンロード

仕様:

- 問題は exam_id と created_by を保持
- 作成時はログインユーザーを created_by に保存
- 選択肢は可変入力だが、画面設計上は 4 択前提
- 正解選択肢は 1 つ以上必須
- 編集時は既存選択肢を一度削除して再生成
- 一覧は exam_id 指定がないと意図的に 0 件表示
- teacher の一覧は created_by = 自分 の問題に限定
- flagged フィルタはログインユーザー基準

### 4-4. 問題 CSV インポート

インポートは [app/Http/Controllers/QuestionController.php](app/Http/Controllers/QuestionController.php) と [app/Support/CsvImportService.php](app/Support/CsvImportService.php) を使います。

仕様:

- 対象試験を選択して CSV をアップロード
- 文字コード調整付きで読み込み
- ヘッダー完全一致を要求
- 1 行ごとにトランザクション処理
- 正解選択肢が 1 つもない行は失敗
- 一部の行で失敗しても、成功行は取り込み継続

期待ヘッダー:

- 問題文
- 全体解説
- 選択肢1, 正解1, 解説1
- 選択肢2, 正解2, 解説2
- 選択肢3, 正解3, 解説3
- 選択肢4, 正解4, 解説4

### 4-5. 受験機能

実装箇所:

- [app/Http/Controllers/QuizController.php](app/Http/Controllers/QuizController.php)

仕様:

- 試験選択画面を表示
- 選択された試験から問題をランダムに 1 問取得
- 表示時に選択肢順をシャッフル
- 正解選択肢 ID、解説、問題 ID、試験 ID をセッションに保存
- 回答時はセッションとリクエストの整合性を確認
- 正誤判定は選択肢 ID の完全一致比較
- 結果画面で問題全体の解説と選択肢ごとの解説を表示

現状の制約:

- 出題履歴の管理はなく、同じ問題が連続で出る可能性があります
- 結果は画面表示のみで、exam_results への保存は行っていません

### 4-6. 要復習フラグ

実装箇所:

- [app/Http/Controllers/FlagController.php](app/Http/Controllers/FlagController.php)
- [app/Models/User.php](app/Models/User.php)
- [app/Models/Question.php](app/Models/Question.php)

仕様:

- questions と users の多対多で管理
- 中間テーブルは flagged_questions
- 問題一覧と編集画面で状態を扱う
- 受験結果画面からも利用可能な設計になっている

### 4-7. 管理者のユーザー管理

実装箇所:

- [app/Http/Controllers/Admin/UserManagementController.php](app/Http/Controllers/Admin/UserManagementController.php)

機能:

- ユーザー一覧
- 個別ユーザー登録
- CSV 一括登録
- 全ユーザー正答率表示
- 教師生徒の紐付け追加と解除

仕様:

- 管理画面で作成できる role は teacher と student のみ
- CSV ヘッダーは 名前, メールアドレス, ロール, パスワード
- role は teacher または student を要求
- パスワードは 8 文字以上

注意点:

- 正答率一覧は exam_results を集計します
- 受験処理が exam_results を書き込んでいないため、現状では実データが増えません

### 4-8. 教師の生徒進捗管理

実装箇所:

- [app/Http/Controllers/Teacher/StudentProgressController.php](app/Http/Controllers/Teacher/StudentProgressController.php)
- [app/Models/TeacherFeedbackComment.php](app/Models/TeacherFeedbackComment.php)

機能:

- 担当生徒の正答率一覧
- 担当生徒へのフィードバックコメント保存
- 教師自身が書いたコメント一覧表示

仕様:

- teacher_student 中間テーブルで担当関係を管理
- 担当外の生徒にはコメント不可
- 正答率は exam_results 集計ベース

## 5. データモデル相関

- Exam 1 : N Question
- Question 1 : N Option
- User N : N Question flagged_questions 経由
- User teacher N : N User student teacher_student 経由
- User teacher 1 : N TeacherFeedbackComment
- User student 1 : N TeacherFeedbackComment
- ExamResult は user と exam に属する集計テーブル

関連ファイル:

- [app/Models/Exam.php](app/Models/Exam.php)
- [app/Models/Question.php](app/Models/Question.php)
- [app/Models/Option.php](app/Models/Option.php)
- [app/Models/User.php](app/Models/User.php)
- [app/Models/ExamResult.php](app/Models/ExamResult.php)
- [app/Models/TeacherFeedbackComment.php](app/Models/TeacherFeedbackComment.php)

## 6. 主要ルート

公開ルート:

- GET /

認証必須:

- GET /dashboard
- GET, PATCH, DELETE /profile
- GET /quiz
- GET /quiz/{exam}
- POST /quiz/{exam}/answer
- POST /questions/{question}/toggle-flag

teacher 以上:

- questions リソース
- questions/import
- questions/import/template
- exams リソース
- teacher/students/progress
- teacher/students/feedback

admin のみ:

- admin/users
- admin/users/import
- admin/users/import/template
- admin/users/accuracy
- admin/teacher-students

## 7. マイグレーションで追加された主要要素

- users.role
- questions.created_by
- flagged_questions
- exam_results
- teacher_student
- teacher_feedback_comments

## 8. 実装上の注意点

- CLI の PHP が 8.2 未満だと artisan が起動できません
- admin:create-user は専用 Command クラスではなく [routes/console.php](routes/console.php) のクロージャコマンドです
- 一般登録では管理者は作成できません
- 正答率画面と教師進捗画面は exam_results 依存ですが、QuizController は成績保存をしていません
- Dockerfile と docker-compose.yml は存在しますが、このドキュメントでは動作確認済みの開発手順として扱っていません

## 9. セットアップの要点

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan admin:create-user "Admin User" "admin@example.com" --password=password123 --verified
composer run dev
```

Windows の copy を使わない環境では、適切なコピーコマンドに読み替えてください。

## 10. 次に整備するとよい点

- QuizController から exam_results を更新する
- 正答率を試験単位や期間単位で見られるようにする
- Docker 構成を実際の起動形態に合わせて見直す

### 8-4. よくあるエラー

- `npm install` で失敗: `node -v` を確認（Node 20推奨）
- `php artisan migrate` でDBエラー: `touch database/database.sqlite` を再実行
- 権限エラー:

```bash
chmod -R ug+rwx storage bootstrap/cache
```

### 8-5. 再起動（2回目以降）

```bash
cd /path/to/StudyTestApp
composer install
npm install
composer run dev
```

依存に変更がなければ、2回目以降は `composer run dev` のみで起動可能。
