<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\PointResetSetting;
use App\Models\TeacherFeedbackComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $activeUsers = null;
        $teacherDashboard = null;
        $studentDashboard = null;

        if ($user?->isAdmin()) {
            $activeUsers = User::query()
                ->where('last_login_at', '>=', now()->subMonths(6))
                ->orderByDesc('last_login_at')
                ->orderBy('id')
                ->paginate(20, ['id', 'name', 'email', 'role', 'last_login_at']);
        }

        if ($user?->isTeacher()) {
            $accuracyFilter = $request->query('accuracy_filter', '70');

            if (! in_array($accuracyFilter, ['all', '50', '70'], true)) {
                $accuracyFilter = '70';
            }

            $questionAnswerSummary = DB::table('question_answer_logs')
                ->select(
                    'question_id',
                    DB::raw('COUNT(*) as attempt_count'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
                )
                ->groupBy('question_id');

            // 教師が作成した問題の正答率を算出し、閾値フィルタ用データを準備する。
            $lowAccuracyQuestions = Question::query()
                ->where('created_by', $user->id)
                ->leftJoin('exams', 'questions.exam_id', '=', 'exams.id')
                ->leftJoinSub($questionAnswerSummary, 'question_answer_summary', function ($join) {
                    $join->on('questions.id', '=', 'question_answer_summary.question_id');
                })
                ->select(
                    'questions.id',
                    'questions.question_text',
                    'exams.name as exam_name',
                    DB::raw('COALESCE(question_answer_summary.attempt_count, 0) as attempt_count'),
                    DB::raw('COALESCE(question_answer_summary.correct_count, 0) as correct_count'),
                    DB::raw('CASE WHEN COALESCE(question_answer_summary.attempt_count, 0) > 0 THEN ROUND((question_answer_summary.correct_count / question_answer_summary.attempt_count) * 100, 1) ELSE NULL END as accuracy_rate')
                )
                ->whereRaw('COALESCE(question_answer_summary.attempt_count, 0) > 0');

            if ($accuracyFilter === '50') {
                $lowAccuracyQuestions->whereRaw('(question_answer_summary.correct_count / question_answer_summary.attempt_count) * 100 <= 50');
            } elseif ($accuracyFilter === '70') {
                $lowAccuracyQuestions->whereRaw('(question_answer_summary.correct_count / question_answer_summary.attempt_count) * 100 <= 70');
            }

            $lowAccuracyQuestions = $lowAccuracyQuestions
                ->orderBy('accuracy_rate')
                ->orderByDesc('attempt_count')
                ->get();

            $assignedStudentIds = $user->students()->pluck('users.id');

            $resultSummary = DB::table('exam_results')
                ->select(
                    'user_id',
                    DB::raw('SUM(score) as total_score'),
                    DB::raw('SUM(question_count) as total_questions')
                )
                ->groupBy('user_id');

            $latestFeedbackSummary = DB::table('teacher_feedback_comments')
                ->select('student_id', DB::raw('MAX(created_at) as last_feedback_at'))
                ->where('teacher_id', $user->id)
                ->groupBy('student_id');

            // 担当生徒ごとのポイント順位と最終フィードバック日時を同時に取得する。
            $assignedStudents = User::query()
                ->whereIn('users.id', $assignedStudentIds)
                ->leftJoinSub($resultSummary, 'result_summary', function ($join) {
                    $join->on('users.id', '=', 'result_summary.user_id');
                })
                ->leftJoinSub($latestFeedbackSummary, 'latest_feedback_summary', function ($join) {
                    $join->on('users.id', '=', 'latest_feedback_summary.student_id');
                })
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.total_points',
                    DB::raw('COALESCE(result_summary.total_score, 0) as total_score'),
                    DB::raw('COALESCE(result_summary.total_questions, 0) as total_questions'),
                    DB::raw('latest_feedback_summary.last_feedback_at as last_feedback_at')
                )
                ->orderByDesc('users.total_points')
                ->orderBy('users.name')
                ->get();

            $assignedStudents->transform(function ($student) {
                $student->accuracy_rate = ((int) $student->total_questions > 0)
                    ? round(((int) $student->total_score / (int) $student->total_questions) * 100, 1)
                    : null;
                // 1か月以上コメントがない、または未実施の生徒を警告対象にする。
                $student->feedback_overdue = $student->last_feedback_at === null
                    || now()->diffInDays($student->last_feedback_at) >= 30;

                return $student;
            });

            $studentsWithoutRecentFeedback = $assignedStudents
                ->filter(fn ($student) => $student->feedback_overdue)
                ->values();

            $teacherDashboard = [
                'accuracyFilter' => $accuracyFilter,
                'lowAccuracyQuestions' => $lowAccuracyQuestions,
                'assignedStudents' => $assignedStudents,
                'studentsWithoutRecentFeedback' => $studentsWithoutRecentFeedback,
                'pointResetSetting' => PointResetSetting::query()->first(),
            ];
        }

        if ($user?->isStudent()) {
            $latestFeedback = TeacherFeedbackComment::query()
                ->where('student_id', $user->id)
                ->with('teacher:id,name')
                ->latest()
                ->first();

            $currentPoints = (int) $user->total_points;

            // 上部カードの順位は全生徒集計で算出する。
            $globalRankingStudents = $this->rankStudentsByPoints(
                User::query()
                    ->where('role', User::ROLE_STUDENT)
                    ->select('id', 'name', 'total_points')
                    ->orderByDesc('total_points')
                    ->orderBy('name')
                    ->get()
            );
            $globalRankingDisplayStudents = $this->selectRankingDisplayStudents($globalRankingStudents, $user->id);

            $teacherRankings = $user->teachers()
                ->select('users.id', 'users.name')
                ->orderBy('users.name')
                ->get()
                ->map(function (User $teacher) use ($user) {
                    $rankedStudents = $this->rankStudentsByPoints(
                        $teacher->students()
                            ->select('users.id', 'users.name', 'users.total_points')
                            ->orderByDesc('users.total_points')
                            ->orderBy('users.name')
                            ->get()
                    );

                    return [
                        'teacher' => $teacher,
                        'students' => $rankedStudents,
                        'displayStudents' => $this->selectRankingDisplayStudents($rankedStudents, $user->id),
                        'currentStudentRank' => $rankedStudents->firstWhere('id', $user->id)?->point_rank,
                    ];
                })
                ->values();

            $pointRank = $globalRankingStudents
                ->firstWhere('id', $user->id)
                ?->point_rank;

            $questionAnswerSummary = DB::table('question_answer_logs')
                ->select(
                    'question_id',
                    DB::raw('COUNT(*) as attempt_count'),
                    DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count')
                )
                ->where('user_id', $user->id)
                ->groupBy('question_id');

            $allAnsweredQuestions = Question::query()
                ->joinSub($questionAnswerSummary, 'question_answer_summary', function ($join) {
                    $join->on('questions.id', '=', 'question_answer_summary.question_id');
                })
                ->leftJoin('exams', 'questions.exam_id', '=', 'exams.id')
                ->select(
                    'questions.id',
                    'questions.question_text',
                    'exams.name as exam_name',
                    DB::raw('question_answer_summary.attempt_count as attempt_count'),
                    DB::raw('question_answer_summary.correct_count as correct_count'),
                    DB::raw('ROUND((question_answer_summary.correct_count / question_answer_summary.attempt_count) * 100, 1) as accuracy_rate')
                )
                ->orderBy('accuracy_rate')
                ->orderByDesc('attempt_count')
                ->get();

            $lowAccuracyPool = $allAnsweredQuestions
                ->filter(fn ($question) => (float) $question->accuracy_rate <= 70.0)
                ->values();

            $questionPool = $lowAccuracyPool->isNotEmpty() ? $lowAccuracyPool : $allAnsweredQuestions;

            $studentDashboard = [
                'latestFeedback' => $latestFeedback,
                'totalPoints' => $currentPoints,
                'pointRank' => $pointRank,
                'weakQuestions' => $questionPool->shuffle()->take(3)->values(),
                'teacherRankings' => $teacherRankings,
                'globalRankingStudents' => $globalRankingStudents,
                'globalRankingDisplayStudents' => $globalRankingDisplayStudents,
            ];
        }

        return view('dashboard', [
            'activeUsers' => $activeUsers,
            'teacherDashboard' => $teacherDashboard,
            'studentDashboard' => $studentDashboard,
        ]);
    }

    public function feedbackHistory(Request $request): View
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403, '生徒のみ閲覧できます。');

        $feedbackComments = TeacherFeedbackComment::query()
            ->where('student_id', $user->id)
            ->with('teacher:id,name')
            ->latest()
            ->paginate(15);

        return view('student.feedback-history', [
            'feedbackComments' => $feedbackComments,
        ]);
    }

    private function rankStudentsByPoints(Collection $students): Collection
    {
        $displayRank = 0;
        $previousPoints = null;

        return $students->transform(function ($student) use (&$displayRank, &$previousPoints) {
            $points = (int) $student->total_points;

            if ($points <= 0) {
                $student->point_rank = null;

                return $student;
            }

            if ($previousPoints === null || $points < $previousPoints) {
                $displayRank++;
            }

            $student->point_rank = $displayRank;
            $previousPoints = $points;

            return $student;
        });
    }

    private function selectRankingDisplayStudents(Collection $rankedStudents, int $currentStudentId, int $topCount = 3): Collection
    {
        $topStudents = $rankedStudents->take($topCount)->values();

        if ($topStudents->contains(fn ($student) => (int) $student->id === $currentStudentId)) {
            return $topStudents;
        }

        $currentStudent = $rankedStudents->first(fn ($student) => (int) $student->id === $currentStudentId);
        if ($currentStudent === null) {
            return $topStudents;
        }

        return $topStudents
            ->push($currentStudent)
            ->sortBy('point_rank')
            ->values();
    }
}