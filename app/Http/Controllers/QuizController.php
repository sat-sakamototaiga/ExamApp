<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Option;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class QuizController extends Controller
{
    /**
     * 試験選択画面を表示
     */
    public function selectExam()
    {
        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得
        return view('quiz.select_exam', compact('exams'));
    }

    /**
     * 試験開始・問題表示
     * 
     * @param \App\Models\Exam $exam 選択された試験のモデルインスタンス
     */
    public function index(Exam $exam)
    {
        // 選択された試験に紐づく問題をランダムに1問取得し、関連する選択肢も取得
        $question = $exam->questions()->with('options')->inRandomOrder()->first();

        // 問題がなければエラーメッセージを表示
        if (!$question) {
            return redirect()->route('quiz.select_exam')->with('error', $exam->name . 'の出題できる問題がありません。');
        }

        // 選択肢をランダムに並べ替える
        $shuffled_options = $question->options->shuffle();

        // 正解の選択肢IDリストと全体/各選択肢の解説をセッションに保存
        $correct_option_ids = $question->options->where('is_correct', true)->pluck('id')->toArray();
        Session::put('current_question_id', $question->id);
        Session::put('correct_option_ids', $correct_option_ids);
        Session::put('overall_explanation', $question->overall_explanation);
        Session::put('current_exam_id', $exam->id); // 現在の試験IDをセッションに保存

        // 各選択肢の解説をセッションに保存
        $option_explanations = [];
        foreach ($question->options as $option) {
            $option_explanations[$option->id] = $option->option_explanation;
        }
        Session::put('option_explanations', $option_explanations);

        return view('quiz.index', compact('exam', 'question', 'shuffled_options'));
    }

    /**
     * 解答を受け付け、正誤判定を行う
     *
     * @param \App\Models\Exam $exam 解答中の試験のモデルインスタンス
     */
    public function answer(Request $request, Exam $exam)
    {
        // バリデーション
        $request->validate([
            'selected_options' => 'array',
            'selected_options.*' => 'integer|exists:options,id',
            'question_id' => 'required|integer',
        ]);

        $selected_option_ids = (array) $request->input('selected_options', []);
        $question_id = $request->input('question_id');

        // セッションから正解情報を取得
        $current_question_id = Session::get('current_question_id');
        $correct_option_ids = Session::get('correct_option_ids', []);
        $overall_explanation = Session::get('overall_explanation');
        $option_explanations = Session::get('option_explanations', []);
        $current_exam_id = Session::get('current_exam_id');

        // セッションに保存された問題IDと現在の問題ID、試験IDが一致するか確認
        if ($question_id != $current_question_id || $exam->id != $current_exam_id) {
            return redirect()->route('quiz.select_exam')->with('error', '問題または試験の整合性エラーが発生しました。再度お試しください。');
        }

        // 正誤判定ロジック
        $selected_option_ids_sorted = collect($selected_option_ids)->sort()->values()->toArray();
        $correct_option_ids_sorted = collect($correct_option_ids)->sort()->values()->toArray();

        $is_correct = ($selected_option_ids_sorted == $correct_option_ids_sorted);

        // 選択肢ごとの情報を取得（表示用）
        $question = Question::with('options')->find($question_id);
        if (!$question) {
            // 万が一問題が見つからない場合の保険
            return redirect()->route('quiz.select_exam')->with('error', '問題が見つかりませんでした。再度お試しください。');
        }

        // 結果ビューに渡すデータ
        $data = [
            'is_correct' => $is_correct,
            'selected_option_ids' => $selected_option_ids,
            'correct_option_ids' => $correct_option_ids,
            'overall_explanation' => $overall_explanation,
            'option_explanations' => $option_explanations,
            'question_options' => $question->options,
            'exam' => $exam, // 結果画面から次の問題へ進むために試験情報を渡す
            'question' => $question, 
        ];

        // セッションをクリア（次の問題のために）
        Session::forget(['current_question_id', 'correct_option_ids', 'overall_explanation', 'option_explanations', 'current_exam_id']);

        return view('quiz.result', $data);
    }
}