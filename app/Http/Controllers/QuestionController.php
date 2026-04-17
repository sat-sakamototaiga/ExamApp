<?php

namespace App\Http\Controllers;

use App\Models\Question; // Questionモデルをインポート
use App\Models\Option; // Optionモデルをインポート
use App\Models\Exam;
use App\Models\User;
use App\Support\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // トランザクションのために追加
use Illuminate\Support\Facades\Log; // デバッグ用にLogファサードを追加
use Exception; // Exceptionクラスをインポート

class QuestionController extends Controller {
    /**
     * 問題一覧を表示
     */
    public function index(Request $request) {
        $this->authorizeQuestionManagement();

        /** @var User $user */
        $user = $request->user();

        // 現在の絞り込み条件を含んだURLをセッションに保存
        session(['questions_index_url' => $request->fullUrl()]);

        $exams = Exam::orderBy('name')->get();
        $selectedExamId = $request->input('exam_id');
        $filter = $request->input('filter');
    
        // クエリビルダを初期化
        $query = Question::query();

        if ($user->isTeacher()) {
            $query->where('created_by', Auth::id());
        }
    
        // 選択された試験IDで絞り込み
        if ($selectedExamId) {
            $query->where('exam_id', $selectedExamId);

            // フラグ付き問題でさらに絞り込み
            if ($filter === 'flagged') {
                $query->whereHas('flaggedByUsers', function ($q) {
                    $q->where('user_id', Auth::id());
                });
            }
        } else {
            // 試験が選択されていない場合、何も表示しない
            $query->whereRaw('1 = 0');
        }

        $questions = $query->orderBy('id')->paginate(10)->withQueryString();

        // ★★★ ここから追加 ★★★
        // ログイン中のユーザーがフラグを立てた問題のIDリストを取得
        $flaggedQuestionIds = $user->flaggedQuestions()->pluck('questions.id');
        // ★★★ ここまで追加 ★★★

        // ★★★ compactに関数を追加 ★★★
        return view('questions.index', compact('questions', 'exams', 'selectedExamId', 'filter', 'flaggedQuestionIds'));
    }

    /**
     * 新しい問題の作成フォームを表示
     */
    public function create() {
        $this->authorizeQuestionManagement();

        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得
        return view('questions.create', compact('exams'));
    }

    /**
     * 新しい問題をデータベースに保存
     */
    public function store(Request $request) {
        $this->authorizeQuestionManagement();

        /** @var User $user */
        $user = $request->user();

        // バリデーションルールを定義
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'question_text' => 'required|string|max:1000',
            'overall_explanation' => 'nullable|string', // 変更

            // 選択肢のバリデーション (少なくとも2つは必要など、要件に応じて調整)
            'options.*.option_text' => 'required|string|max:255',
            'options.*.is_correct' => 'nullable|boolean', // チェックボックスなのでnullable
            'options.*.option_explanation' => 'nullable|string',

            // 正解のチェックが最低1つ、最大いくつ必要か (複数選択のため)
            'correct_options' => 'required|array|min:1', // 少なくとも1つの正解が選択されていること
            'correct_options.*' => 'integer', // 配列の各要素が整数であること
        ]);

        // トランザクション開始
        DB::beginTransaction();
        try {
            // 問題を作成
            $question = Question::create([
                'exam_id' => $request->input('exam_id'),
                'created_by' => $user->id,
                'question_text' => $request->input('question_text'),
                'overall_explanation' => $request->input('overall_explanation'),
            ]);

            // 各選択肢を保存
            foreach ($request->input('options') as $key => $option_data) {
                // 正解のチェックボックスが送られてきているか確認
                $is_correct = isset($request->input('correct_options')[$key]);

                $question->options()->create([
                    'option_text' => $option_data['option_text'],
                    'is_correct' => $is_correct,
                    'option_explanation' => $option_data['option_explanation'],
                ]);
            }

            //フラグのリレーションを更新
            if ($request->has('is_flagged')) {
                // ログインユーザーに紐づけてフラグを追加
                $user->flaggedQuestions()->attach($question->id);
            }

            DB::commit(); // 全ての処理が成功したらコミット
            return redirect()->route('questions.index')->with('success', '問題が正常に登録されました。');
        } catch (\Exception $e) {
            DB::rollBack(); // エラーが発生したらロールバック
            return back()->withInput()->with('error', '問題の登録中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 特定の問題を表示 (今回は使わない可能性ありだが、ひな形として残す)
     */
    public function show(Question $question) {
        return view('questions.show', compact('question'));
    }

    /**
     * 特定の問題の編集フォームを表示
     */
    public function edit(Question $question) {
        $this->authorizeQuestionManagement();
        $this->authorizeQuestionOwnership($question);

        /** @var User $user */
        $user = Auth::user();

        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得

        // 現在のユーザーがこの問題にフラグを立てているかどうかを確認
        $isFlagged = $user->flaggedQuestions()->where('question_id', $question->id)->exists();

        // 関連するoptionsも自動的に取得されている
        return view('questions.edit', compact('question', 'exams', 'isFlagged'));
    }

    /**
     * 特定の問題をデータベースで更新
     */
    // app/Http/Controllers/QuestionController.php

    public function update(Request $request, Question $question) {
        $this->authorizeQuestionManagement();
        $this->authorizeQuestionOwnership($question);

        /** @var User $user */
        $user = $request->user();

        // 1. 入力値のバリデーション
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'question_text' => 'required|string|max:1000',
            'overall_explanation' => 'nullable|string',
            'options.*.option_text' => 'required|string|max:255',
            'correct_options' => 'required|array|min:1',
        ]);

        try {
            // 2. 問題の主要情報を更新 (これは正常に動作している部分)
            $question->update([
                'exam_id' => $request->input('exam_id'),
                'question_text' => $request->input('question_text'),
                'overall_explanation' => $request->input('overall_explanation'),
            ]);

            // 3. 選択肢を更新 (これも正常に動作している部分)
            $question->options()->delete();
            foreach ($request->input('options') as $key => $option_data) {
                if (empty($option_data['option_text'])) {
                    continue;
                }
                $is_correct = isset($request->input('correct_options')[$key]);
                $question->options()->create([
                    'option_text' => $option_data['option_text'],
                    'is_correct' => $is_correct,
                    'option_explanation' => $option_data['option_explanation'],
                ]);
            }

            // 4. フラグのリレーションを更新 (問題の核心部分)
            if ($request->has('is_flagged')) {
                // チェックがあれば、ログインユーザーと問題の間に紐付けを作成
                $user->flaggedQuestions()->syncWithoutDetaching($question->id);
            } else {
                // チェックがなければ、紐付けを解除
                $user->flaggedQuestions()->detach($question->id);
            }

            // 5. 成功時のメッセージと共に、絞り込み状態を保持した一覧へリダイレクト
            return redirect(session('questions_index_url', route('questions.index')))->with('success', '問題が正常に更新されました。');
        } catch (\Exception $e) {
            // 6. 万が一エラーが発生した場合
            return back()->withInput()->with('error', '問題の更新中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 特定の問題をデータベースから削除
     */
    public function destroy(Question $question) {
        $this->authorizeQuestionManagement();
        $this->authorizeQuestionOwnership($question);

        // 問題を削除
        $question->delete();

        // 問題一覧ページにリダイレクトし、成功メッセージを表示
        return redirect()->route('questions.index')->with('success', '問題が正常に削除されました。');
    }

    /**
     * 問題インポートフォームを表示する
     */
    public function importForm() {
        $this->authorizeQuestionManagement();

        $exams = Exam::orderBy('name')->get();
        return view('questions.import', compact('exams'));
    }

    /**
     * インポート用のCSVテンプレートをダウンロードする
     */
    public function downloadTemplate(CsvImportService $csvImportService) {
        $this->authorizeQuestionManagement();

        $header = [
            '問題文', '全体解説',
            '選択肢1', '正解1', '解説1',
            '選択肢2', '正解2', '解説2',
            '選択肢3', '正解3', '解説3',
            '選択肢4', '正解4', '解説4',
        ];

        $sampleRow = [
            'サンプル問題: 日本の首都はどこですか？',
            '日本の首都は東京です。',
            '東京', '1', '日本の首都は東京です。',
            '大阪', '0', '大阪は首都ではありません。',
            '名古屋', '0', '名古屋は首都ではありません。',
            '福岡', '0', '福岡は首都ではありません。',
        ];

        return $csvImportService->streamTemplateDownload('questions_import_template.csv', $header, [$sampleRow]);
    }
    
    /**
     * アップロードされたCSVファイルから問題をインポートする
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request, CsvImportService $csvImportService) {
        $this->authorizeQuestionManagement();

        // 1. アップロードされたファイルのバリデーション
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // 必須、ファイル形式はCSVまたはTXT、最大2MB
            'exam_id' => 'required|exists:exams,id', // どの試験にインポートするか (追加)
        ]);

        try {
            $reader = $csvImportService->createReaderFromUpload($request->file('csv_file'));
        } catch (Exception $e) {
            return back()->withInput()->withErrors([$e->getMessage()]);
        }

        $csvStream = $reader['stream'];
        $header = $reader['header'];

        $importedCount = 0; // インポート成功数
        $errorMessages = []; // エラーメッセージを格納する配列
        $lineNumber = 1; // 処理中の行番号 (ヘッダーを1行目とする)

        // 期待されるCSVヘッダーの形式
        // 例: 問題文,全体解説,選択肢1,正解1,解説1,選択肢2,正解2,解説2,選択肢3,正解3,解説3,選択肢4,正解4,解説4
        $expectedHeader = [
            '問題文', '全体解説',
            '選択肢1', '正解1', '解説1',
            '選択肢2', '正解2', '解説2',
            '選択肢3', '正解3', '解説3',
            '選択肢4', '正解4', '解説4',
        ];

        // 3. CSVヘッダーの整合性チェック (簡易的なもの)
        if ($header !== $expectedHeader) {
            fclose($csvStream);
            return back()->withInput()->withErrors([
                'CSVファイルのヘッダー形式が正しくありません。期待されるヘッダー: ' . implode(', ', $expectedHeader),
            ]);
        }

        $selectedExamId = $request->input('exam_id'); // 選択された試験IDを取得

        // 4. 各行を処理してデータベースに挿入
        while (($row = fgetcsv($csvStream)) !== false) {
            $lineNumber++; // 行番号をインクリメント

            if ($row === [null]) {
                continue; // 空行はスキップ
            }

            // 行の列数とヘッダーの列数が一致しない場合はエラー
            if (count($row) != count($header)) {
                $errorMessages[] = "{$lineNumber}行目: 列数がヘッダーと一致しません。スキップしました。";
                continue;
            }

            // ヘッダーと行データをキー・値のペアで関連付ける
            $data = array_combine($header, $row);
            if ($data === false) {
                $errorMessages[] = "{$lineNumber}行目: CSVデータの読み取りに失敗しました。";
                continue;
            }

            $data = array_map(static fn($value) => is_string($value) ? trim($value) : $value, $data);

            // 問題文が空の場合はスキップ
            if (empty($data['問題文'])) {
                $errorMessages[] = "{$lineNumber}行目: 問題文が空のためスキップしました。";
                continue;
            }

            // データベーストランザクションを開始
            DB::beginTransaction();
            try {
                // 問題を作成
                $question = Question::create([
                    'exam_id' => $selectedExamId, // 選択された試験IDを問題に紐付ける
                    'created_by' => $request->user()->id,
                    'question_text' => $data['問題文'],
                    'overall_explanation' => $data['全体解説'] ?? null, // '全体解説'がなければnull
                ]);

                // 選択肢の作成
                $hasCorrectOption = false; // 正解の選択肢が一つ以上あるかチェック
                for ($i = 1; $i <= 4; $i++) {
                    $optionText = $data["選択肢{$i}"] ?? null;
                    $isCorrect = (isset($data["正解{$i}"]) && $data["正解{$i}"] == '1'); // '正解X'が'1'ならtrue
                    $optionExplanation = $data["解説{$i}"] ?? null;

                    if (!empty($optionText)) { // 選択肢のテキストが空でなければ保存
                        $question->options()->create([
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect,
                            'option_explanation' => $optionExplanation,
                        ]);
                        if ($isCorrect) {
                            $hasCorrectOption = true;
                        }
                    }
                }

                // 正解の選択肢が一つもなければエラーとする
                if (!$hasCorrectOption) {
                    throw new Exception('正解の選択肢が一つも設定されていません。');
                }

                $importedCount++; // 成功数をカウント
                DB::commit(); // 全ての処理が成功したらコミット
            } catch (Exception $e) {
                DB::rollBack(); // エラーが発生したらロールバック
                $errorMessages[] = "{$lineNumber}行目: インポート中にエラーが発生しました - " . $e->getMessage();
                Log::error("CSV Import Error on line {$lineNumber}: " . $e->getMessage() . ' Data: ' . json_encode($data));
            }
        }

        fclose($csvStream);

        // 5. 結果メッセージの表示
        if (count($errorMessages) > 0) {
            if ($importedCount > 0) {
                array_unshift($errorMessages, "{$importedCount}件の問題をインポートしましたが、一部の行でエラーが発生しました。");
            }
            return back()->withInput()->withErrors($errorMessages);
        } else {
            return back()->with('success', "{$importedCount}件の問題を正常にインポートしました。");
        }
    }

    private function authorizeQuestionManagement(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user?->hasRoleLevel(User::ROLE_TEACHER)) {
            abort(403, 'この操作を実行する権限がありません。');
        }
    }

    private function authorizeQuestionOwnership(Question $question): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return;
        }

        if (! $user?->isTeacher() || $question->created_by !== $user->id) {
            abort(403, '自分が作成した問題のみ操作できます。');
        }
    }
}
