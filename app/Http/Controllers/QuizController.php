<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class QuizController extends Controller
{
    private const QUIZ_STATE_KEY = 'quiz_state';
    private const MODE_NORMAL = 'normal';
    private const MODE_FLAGGED = 'flagged';
    private const MODE_RANDOM_COUNT = 'random_count';
    private const PERFECT_BONUS_POINTS = 10;

    private const DIFFICULTY_POINTS = [
        Question::DIFFICULTY_EASY => 1,
        Question::DIFFICULTY_NORMAL => 3,
        Question::DIFFICULTY_EXPERT => 5,
    ];

    /**
     * 試験選択画面を表示
     */
    public function selectExam()
    {
        $this->clearQuizState();

        $exams = Exam::orderBy('name')->get(); // 全ての試験を取得
        return view('quiz.select_exam', compact('exams'));
    }

    /**
     * 試験開始・問題表示
     * 
     * @param \App\Models\Exam $exam 選択された試験のモデルインスタンス
     */
    public function index(Request $request, Exam $exam)
    {
        if ($this->isLikelyReloadRequest($request)) {
            $this->clearQuizState();
        }

        $validated = $request->validate([
            'mode' => 'nullable|in:normal,flagged,random_count',
            'count' => 'nullable|integer|min:1|max:200',
        ]);

        $mode = $validated['mode'] ?? self::MODE_NORMAL;
        $requestedCount = (int) ($validated['count'] ?? 10);

        // 出題モードごとに最初の問題プールを組み立てる。
        $poolQuestionIds = $this->buildPoolQuestionIds($exam, $mode, $requestedCount, $request->user());

        if (empty($poolQuestionIds)) {
            $message = match ($mode) {
                self::MODE_FLAGGED => $exam->name . 'にフラグ付き問題がありません。',
                default => $exam->name . 'の出題できる問題がありません。',
            };

            return redirect()->route('quiz.select_exam')->with('error', $message);
        }

        $targetCount = $mode === self::MODE_RANDOM_COUNT
            ? min($requestedCount, count($poolQuestionIds))
            : count($poolQuestionIds);

        // quiz_state は出題継続・再表示・遷移制御を一元管理するセッション状態。
        $state = [
            'exam_id' => $exam->id,
            'mode' => $mode,
            'target_count' => $targetCount,
            'pool_question_ids' => $poolQuestionIds,
            'solved_question_ids' => [],
            'last_question_id' => null,
            'current_question_id' => null,
            'correct_option_ids' => [],
            'overall_explanation' => null,
            'option_explanations' => [],
            'lock_navigation' => $mode === self::MODE_RANDOM_COUNT,
        ];

        Session::put(self::QUIZ_STATE_KEY, $state);

        return $this->showNextQuestion($exam, $state);
    }

    /**
     * 現在の出題状態を再表示（遷移制御用）
     */
    public function resume(Exam $exam)
    {
        $state = $this->getValidStateForExam($exam);

        if (!$state) {
            return redirect()->route('quiz.select_exam')->with('error', '出題状態が見つかりません。再度試験を選択してください。');
        }

        if (!empty($state['current_question_id'])) {
            return $this->renderCurrentQuestion($exam, $state);
        }

        return $this->showNextQuestion($exam, $state);
    }

    /**
     * 次の問題を表示
     */
    public function next(Exam $exam)
    {
        $state = $this->getValidStateForExam($exam);

        if (!$state) {
            return redirect()->route('quiz.select_exam')->with('error', '出題状態が見つかりません。再度試験を選択してください。');
        }

        return $this->showNextQuestion($exam, $state);
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

        $state = $this->getValidStateForExam($exam);

        if (!$state) {
            return redirect()->route('quiz.select_exam')->with('error', '出題状態が見つかりません。再度試験を選択してください。');
        }

        // セッションから正解情報を取得
        $current_question_id = $state['current_question_id'] ?? null;
        $correct_option_ids = $state['correct_option_ids'] ?? [];
        $overall_explanation = $state['overall_explanation'] ?? null;
        $option_explanations = $state['option_explanations'] ?? [];

        // セッションに保存された問題IDと現在の問題IDが一致するか確認
        if ((int) $question_id !== (int) $current_question_id) {
            return redirect()->route('quiz.select_exam')->with('error', '問題または試験の整合性エラーが発生しました。再度お試しください。');
        }

        // 選択肢ごとの情報を取得（表示・ポイント計算用）
        $question = Question::with('options')
            ->where('exam_id', $exam->id)
            ->find($question_id);

        if (!$question) {
            return redirect()->route('quiz.select_exam')->with('error', '問題が見つかりませんでした。再度お試しください。');
        }

        // 正誤判定ロジック
        $selected_option_ids_sorted = collect($selected_option_ids)->sort()->values()->toArray();
        $correct_option_ids_sorted = collect($correct_option_ids)->sort()->values()->toArray();

        $is_correct = ($selected_option_ids_sorted == $correct_option_ids_sorted);
        $wasAlreadySolved = in_array((int) $question_id, array_map('intval', $state['solved_question_ids'] ?? []), true);

        $awardedQuestionPoints = 0;

        if ($is_correct && !$wasAlreadySolved) {
            $state['solved_question_ids'] = array_values(array_unique([
                ...($state['solved_question_ids'] ?? []),
                (int) $question_id,
            ]));

            $awardedQuestionPoints = $this->resolvePointsForDifficulty((string) $question->difficulty);

            if ($request->user()?->isStudent() && $awardedQuestionPoints > 0) {
                $request->user()->increment('total_points', $awardedQuestionPoints);
            }
        }

        // 連続同一問題を防ぐため、直前問題を記録
        $state['last_question_id'] = (int) $question_id;
        $state['current_question_id'] = null;
        $state['correct_option_ids'] = [];
        $state['overall_explanation'] = null;
        $state['option_explanations'] = [];
        Session::put(self::QUIZ_STATE_KEY, $state);

        // 結果ビューに渡すデータ
        $remainingCount = $this->remainingQuestionCount($state);
        $isFinished = $remainingCount === 0;
        $bonusPointsAwarded = 0;

        if ($isFinished && $is_correct) {
            $basePoints = $this->resolveTotalPointsForQuestions($state['pool_question_ids'] ?? []);
            $bonusPointsAwarded = self::PERFECT_BONUS_POINTS;

            if ($request->user()?->isStudent()) {
                $request->user()->increment('total_points', $bonusPointsAwarded);
            }

            ExamResult::create([
                'user_id' => $request->user()->id,
                'exam_id' => $exam->id,
                'score' => count($state['solved_question_ids'] ?? []),
                'question_count' => count($state['pool_question_ids'] ?? []),
                'points_earned' => $basePoints + $bonusPointsAwarded,
                'bonus_points' => $bonusPointsAwarded,
            ]);
        }

        $data = [
            'is_correct' => $is_correct,
            'is_finished' => $isFinished,
            'awarded_question_points' => $awardedQuestionPoints,
            'awarded_bonus_points' => $bonusPointsAwarded,
            'remaining_count' => $remainingCount,
            'progress_total' => count($state['pool_question_ids'] ?? []),
            'progress_correct' => count($state['solved_question_ids'] ?? []),
            'quiz_mode' => $state['mode'] ?? self::MODE_NORMAL,
            'selected_option_ids' => $selected_option_ids,
            'correct_option_ids' => $correct_option_ids,
            'overall_explanation' => $overall_explanation,
            'option_explanations' => $option_explanations,
            'question_options' => $question->options,
            'exam' => $exam, // 結果画面から次の問題へ進むために試験情報を渡す
            'question' => $question,
        ];

        return view('quiz.result', $data);
    }

    private function resolvePointsForDifficulty(string $difficulty): int
    {
        return self::DIFFICULTY_POINTS[$difficulty] ?? self::DIFFICULTY_POINTS[Question::DIFFICULTY_NORMAL];
    }

    private function resolveTotalPointsForQuestions(array $questionIds): int
    {
        if (empty($questionIds)) {
            return 0;
        }

        $difficulties = Question::query()
            ->whereIn('id', array_map('intval', $questionIds))
            ->pluck('difficulty');

        return $difficulties
            ->map(fn ($difficulty) => $this->resolvePointsForDifficulty((string) $difficulty))
            ->sum();
    }

    private function buildPoolQuestionIds(Exam $exam, string $mode, int $requestedCount, ?User $user): array
    {
        $baseQuery = $exam->questions()->select('questions.id');

        if ($mode === self::MODE_FLAGGED) {
            if (!$user) {
                return [];
            }

            $baseQuery->whereHas('flaggedByUsers', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $allIds = $baseQuery->pluck('questions.id')->map(fn ($id) => (int) $id)->toArray();

        if ($mode !== self::MODE_RANDOM_COUNT) {
            return $allIds;
        }

        if (empty($allIds)) {
            return [];
        }

        // ランダム指定数モードでは、ここで初回プールのサイズを確定する。
        $limit = min($requestedCount, count($allIds));

        return collect($allIds)->shuffle()->take($limit)->values()->toArray();
    }

    private function showNextQuestion(Exam $exam, array $state)
    {
        $remainingIds = $this->remainingQuestionIds($state);

        if (empty($remainingIds)) {
            $this->clearQuizState();

            return redirect()->route('quiz.select_exam')->with('success', '全ての問題に正解したため、出題を終了しました。');
        }

        $candidateIds = $remainingIds;
        $lastQuestionId = $state['last_question_id'] ?? null;

        // 可能な限り直前と同じ問題を避ける
        if ($lastQuestionId && count($candidateIds) > 1) {
            $candidateIds = array_values(array_filter(
                $candidateIds,
                fn ($id) => (int) $id !== (int) $lastQuestionId
            ));
        }

        if (empty($candidateIds)) {
            $candidateIds = $remainingIds;
        }

        $nextQuestionId = (int) collect($candidateIds)->random();

        $question = Question::with('options')
            ->where('exam_id', $exam->id)
            ->find($nextQuestionId);

        if (!$question) {
            $this->clearQuizState();
            return redirect()->route('quiz.select_exam')->with('error', '問題データが取得できませんでした。再度お試しください。');
        }

        $state['current_question_id'] = $question->id;
        $state['correct_option_ids'] = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $state['overall_explanation'] = $question->overall_explanation;
        $state['option_explanations'] = $question->options
            ->pluck('option_explanation', 'id')
            ->mapWithKeys(fn ($explanation, $id) => [(int) $id => $explanation])
            ->toArray();

        Session::put(self::QUIZ_STATE_KEY, $state);

        return $this->renderCurrentQuestion($exam, $state, $question);
    }

    private function renderCurrentQuestion(Exam $exam, array $state, ?Question $question = null)
    {
        $question ??= Question::with('options')->where('exam_id', $exam->id)->find($state['current_question_id'] ?? 0);

        if (!$question) {
            $this->clearQuizState();
            return redirect()->route('quiz.select_exam')->with('error', '現在の問題が見つかりません。再度試験を開始してください。');
        }

        $shuffled_options = $question->options->shuffle();

        return view('quiz.index', [
            'exam' => $exam,
            'question' => $question,
            'shuffled_options' => $shuffled_options,
            'quiz_mode' => $state['mode'] ?? self::MODE_NORMAL,
            'remaining_count' => $this->remainingQuestionCount($state),
            'progress_total' => count($state['pool_question_ids'] ?? []),
            'progress_correct' => count($state['solved_question_ids'] ?? []),
        ]);
    }

    private function remainingQuestionIds(array $state): array
    {
        $poolIds = array_map('intval', $state['pool_question_ids'] ?? []);
        $solvedIds = array_map('intval', $state['solved_question_ids'] ?? []);

        // 正解済みIDを除外した残問題IDを毎回算出することで、途中状態の整合性を保つ。
        return array_values(array_diff($poolIds, $solvedIds));
    }

    private function remainingQuestionCount(array $state): int
    {
        return count($this->remainingQuestionIds($state));
    }

    private function getValidStateForExam(Exam $exam): ?array
    {
        $state = Session::get(self::QUIZ_STATE_KEY);

        if (!is_array($state)) {
            return null;
        }

        if ((int) ($state['exam_id'] ?? 0) !== (int) $exam->id) {
            return null;
        }

        return $state;
    }

    private function clearQuizState(): void
    {
        Session::forget(self::QUIZ_STATE_KEY);
    }

    private function isLikelyReloadRequest(Request $request): bool
    {
        $cacheControl = strtolower((string) $request->header('Cache-Control', ''));
        $pragma = strtolower((string) $request->header('Pragma', ''));

        return str_contains($cacheControl, 'max-age=0')
            || str_contains($cacheControl, 'no-cache')
            || str_contains($pragma, 'no-cache');
    }
}