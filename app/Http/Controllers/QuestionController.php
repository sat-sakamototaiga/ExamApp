<?php

namespace App\Http\Controllers;

use App\Models\Question; // Questionモデルをインポート
use App\Models\Option; // Optionモデルをインポート
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // トランザクションのために追加
use Illuminate\Support\Facades\Log; // デバッグ用にLogファサードを追加
use Exception; // Exceptionクラスをインポート

class QuestionController extends Controller
{
    /**
     * 問題一覧を表示
     */
    public function index()
    {
        // 全ての問題を取得し、作成日時の新しい順に並べ替えて表示
        $questions = Question::orderBy('id')->paginate(10); // ページネーションを追加
        return view('questions.index', compact('questions'));
    }

    /**
     * 新しい問題の作成フォームを表示
     */
    public function create()
    {
        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得
        return view('questions.create', compact('exams'));
    }

    /**
     * 新しい問題をデータベースに保存
     */
    public function store(Request $request)
    {
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
    public function show(Question $question)
    {
        return view('questions.show', compact('question'));
    }

    /**
     * 特定の問題の編集フォームを表示
     */
    public function edit(Question $question)
    {
        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得
        // 関連するoptionsも自動的に取得されている
        return view('questions.edit', compact('question', 'exams'));
    }

    /**
     * 特定の問題をデータベースで更新
     */
    public function update(Request $request, Question $question)
    {
         $request->validate([
            'exam_id' => 'required|exists:exams,id', 
            'question_text' => 'required|string|max:1000',
            'overall_explanation' => 'nullable|string',

            'options.*.id' => 'nullable|integer|exists:options,id', // 既存選択肢のID
            'options.*.option_text' => 'required|string|max:255',
            'options.*.is_correct' => 'nullable|boolean',
            'options.*.option_explanation' => 'nullable|string',

            'correct_options' => 'required|array|min:1', // 少なくとも1つの正解が選択されていること
            'correct_options.*' => 'integer',
        ]);

        DB::beginTransaction();
        try {
            // 問題を更新
            $question->update([
                'exam_id' => $request->input('exam_id'),
                'question_text' => $request->input('question_text'),
                'overall_explanation' => $request->input('overall_explanation'),
            ]);

            // 既存の選択肢を一度全て削除し、新しい選択肢で再作成する（シンプルにするため）
            // より高度な更新は、既存の選択肢との比較ロジックが必要
            $question->options()->delete();

            foreach ($request->input('options') as $key => $option_data) {
                $is_correct = isset($request->input('correct_options')[$key]);

                $question->options()->create([
                    'option_text' => $option_data['option_text'],
                    'is_correct' => $is_correct,
                    'option_explanation' => $option_data['option_explanation'],
                ]);
            }

            DB::commit();
            return redirect()->route('questions.index')->with('success', '問題が正常に更新されました。');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', '問題の更新中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 特定の問題をデータベースから削除
     */
    public function destroy(Question $question)
    {
        // 問題を削除
        $question->delete();

        // 問題一覧ページにリダイレクトし、成功メッセージを表示
        return redirect()->route('questions.index')->with('success', '問題が正常に削除されました。');
    }

    /**
     * 問題インポートフォームを表示する
     */
    public function importForm()
    {
        $exam = \App\Models\Exam::orderBy('name')->get();
        return view('Questions.import', compact('exams'));
    }
    
    /**
     * アップロードされたCSVファイルから問題をインポートする
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        // 1. アップロードされたファイルのバリデーション
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // 必須、ファイル形式はCSVまたはTXT、最大2MB
            'exam_id' => 'required|exists:exams,id', // どの試験にインポートするか (追加)
        ]);

        $file = $request->file('csv_file');
        $filePath = $file->getRealPath(); // アップロードされたファイルの一時パス

        // 2. CSVファイルの読み込みと文字コード変換
        // Windowsで作成されたCSVはShift-JISの場合が多いのでUTF-8に変換
        $csvData = file_get_contents($filePath);
        if (!mb_check_encoding($csvData, 'UTF-8')) {
            $csvData = mb_convert_encoding($csvData, 'UTF-8', 'SJIS-win');
        }

        // 行ごとに分割し、ヘッダー行を取得
        $lines = explode(PHP_EOL, $csvData);
        $header = str_getcsv(array_shift($lines)); // 最初の行をヘッダーとして取得

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
            '選択肢4', '正解4', '解説4'
        ];

        // 3. CSVヘッダーの整合性チェック (簡易的なもの)
        if (count($header) < count($expectedHeader) || array_diff($expectedHeader, $header)) {
             return back()->with('error', 'CSVファイルのヘッダー形式が正しくありません。期待されるヘッダー: ' . implode(', ', $expectedHeader));
        }

        $selectedExamId = $request->input('exam_id'); // 選択された試験IDを取得

        // 4. 各行を処理してデータベースに挿入
        foreach ($lines as $line) {
            $lineNumber++; // 行番号をインクリメント
            if (trim($line) === '') {
                continue; // 空行はスキップ
            }

            $row = str_getcsv($line); // CSVの行を配列に変換

            // 行の列数とヘッダーの列数が一致しない場合はエラー
            if (count($row) != count($header)) {
                $errorMessages[] = "{$lineNumber}行目: 列数がヘッダーと一致しません。スキップしました。";
                continue;
            }

            // ヘッダーと行データをキー・値のペアで関連付ける
            $data = array_combine($header, $row);

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
                Log::error("CSV Import Error on line {$lineNumber}: " . $e->getMessage() . " Data: " . json_encode($data));
            }
        }

        // 5. 結果メッセージの表示
        if (count($errorMessages) > 0) {
            $message = "{$importedCount}件の問題をインポートしました。しかし、以下のエラーが発生しました:<br>" . implode('<br>', $errorMessages);
            return back()->withInput()->with('error', $message); // エラーメッセージと入力データを戻す
        } else {
            return back()->with('success', "{$importedCount}件の問題を正常にインポートしました。");
        }
    }
}