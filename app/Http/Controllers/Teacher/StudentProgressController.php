<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PointHistory;
use App\Models\PointResetSetting;
use App\Models\TeacherFeedbackComment;
use App\Models\User;
use App\Support\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function __construct(private PointService $pointService)
    {
    }

    public function index(Request $request): View
    {
        $teacher = $request->user();
        $this->applyAutomaticPointResetIfDue($teacher);

        $pointResetSetting = PointResetSetting::query()
            ->where('teacher_id', $teacher->id)
            ->first();

        $assignedStudentIds = $teacher->students()->pluck('users.id');
        // 担当生徒ごとの最新フィードバック日時を抽出してリマインド判定に使う。
        $latestFeedbackSummary = DB::table('teacher_feedback_comments')
            ->select('student_id', DB::raw('MAX(created_at) as last_feedback_at'))
            ->where('teacher_id', $teacher->id)
            ->groupBy('student_id');

        $resultSummary = DB::table('exam_results')
            ->select(
                'user_id',
                DB::raw('SUM(score) as total_score'),
                DB::raw('SUM(question_count) as total_questions')
            )
            ->groupBy('user_id');

        $students = User::query()
            ->whereIn('id', $assignedStudentIds)
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
            ->groupBy('users.id', 'users.name', 'users.email', 'users.total_points', 'latest_feedback_summary.last_feedback_at')
            ->orderBy('users.name')
            ->get();

        $students->transform(function ($student) {
            $student->accuracy_rate = ((int) $student->total_questions > 0)
                ? round(((int) $student->total_score / (int) $student->total_questions) * 100, 1)
                : null;
            // 30日以上未コメント、または未コメントを期限超過として扱う。
            $student->feedback_overdue = $student->last_feedback_at === null
                || now()->diffInDays($student->last_feedback_at) >= 30;

            return $student;
        });

        $studentsWithoutRecentFeedback = $students
            ->filter(fn ($student) => $student->feedback_overdue)
            ->values();

        $feedbackByStudent = TeacherFeedbackComment::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('student_id', $assignedStudentIds)
            ->with('student:id,name')
            ->latest()
            ->get();

        return view('teacher.student-progress', compact('students', 'feedbackByStudent', 'pointResetSetting', 'studentsWithoutRecentFeedback'));
    }

    public function updatePointResetInterval(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        $validated = $request->validate([
            'reset_interval_days' => 'nullable|integer|min:1|max:365',
        ]);

        $setting = PointResetSetting::query()->firstOrNew([
            'teacher_id' => $teacher->id,
        ]);
        $setting->reset_interval_days = $validated['reset_interval_days'] ?? null;
        $setting->updated_by = $teacher->id;

        if ($setting->last_reset_at === null && $setting->reset_interval_days !== null) {
            $setting->last_reset_at = now();
        }

        $setting->save();

        return back()
            ->with('success', 'ポイント自動リセット間隔を更新しました。')
            // ダッシュボード側で操作完了UIを切り替えるためのフラグ。
            ->with('completed_action', 'auto_reset_updated');
    }

    public function resetAllStudentPoints(Request $request): RedirectResponse
    {
        $teacher = $request->user();
        $assignedStudents = $teacher->students()->select('users.id')->get();

        $resetCount = $this->pointService->resetPointsForTeacherStudents(
            teacher: $teacher,
            students: $assignedStudents,
            eventType: PointHistory::EVENT_MANUAL_RESET
        );

        $setting = PointResetSetting::query()->firstOrNew([
            'teacher_id' => $teacher->id,
        ]);
        $setting->last_reset_at = now();
        $setting->updated_by = $teacher->id;
        $setting->save();

        return back()
            ->with('success', "担当生徒のポイントをリセットしました（{$resetCount}名）。")
            // ダッシュボード側で完了ポップアップを表示するためのフラグ。
            ->with('completed_action', 'points_reset');
    }

    public function storeFeedback(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:1000',
        ]);

        $isAssigned = $teacher->students()->where('users.id', $validated['student_id'])->exists();
        if (! $isAssigned) {
            abort(403, '担当外の生徒にはコメントできません。');
        }

        TeacherFeedbackComment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $validated['student_id'],
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'フィードバックコメントを保存しました。');
    }

    private function applyAutomaticPointResetIfDue(User $teacher): void
    {
        $setting = PointResetSetting::query()
            ->where('teacher_id', $teacher->id)
            ->first();

        if (! $setting || $setting->reset_interval_days === null) {
            return;
        }

        if ($setting->last_reset_at === null) {
            $setting->last_reset_at = now();
            $setting->updated_by = $teacher->id;
            $setting->save();

            return;
        }

        $nextResetAt = $setting->last_reset_at->copy()->addDays((int) $setting->reset_interval_days);

        if (now()->lt($nextResetAt)) {
            return;
        }

        $assignedStudents = $teacher->students()->select('users.id')->get();

        $this->pointService->resetPointsForTeacherStudents(
            teacher: $teacher,
            students: $assignedStudents,
            eventType: PointHistory::EVENT_AUTO_RESET
        );

        $setting->last_reset_at = now();
        $setting->updated_by = $teacher->id;
        $setting->save();
    }
}
