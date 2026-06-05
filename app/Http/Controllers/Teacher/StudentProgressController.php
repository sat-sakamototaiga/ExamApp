<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PointResetSetting;
use App\Models\TeacherFeedbackComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentProgressController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $request->user();
        $this->applyAutomaticPointResetIfDue($teacher);

        $pointResetSetting = PointResetSetting::query()->first();

        $assignedStudentIds = $teacher->students()->pluck('users.id');

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
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.total_points',
                DB::raw('COALESCE(result_summary.total_score, 0) as total_score'),
                DB::raw('COALESCE(result_summary.total_questions, 0) as total_questions')
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.total_points')
            ->orderBy('users.name')
            ->get();

        $students->transform(function ($student) {
            $student->accuracy_rate = ((int) $student->total_questions > 0)
                ? round(((int) $student->total_score / (int) $student->total_questions) * 100, 1)
                : null;

            return $student;
        });

        $feedbackByStudent = TeacherFeedbackComment::query()
            ->where('teacher_id', $teacher->id)
            ->whereIn('student_id', $assignedStudentIds)
            ->with('student:id,name')
            ->latest()
            ->get();

        return view('teacher.student-progress', compact('students', 'feedbackByStudent', 'pointResetSetting'));
    }

    public function updatePointResetInterval(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        $validated = $request->validate([
            'reset_interval_days' => 'nullable|integer|min:1|max:365',
        ]);

        $setting = PointResetSetting::query()->first() ?? new PointResetSetting();
        $setting->reset_interval_days = $validated['reset_interval_days'] ?? null;
        $setting->updated_by = $teacher->id;

        if ($setting->last_reset_at === null && $setting->reset_interval_days !== null) {
            $setting->last_reset_at = now();
        }

        $setting->save();

        return back()->with('success', 'ポイント自動リセット間隔を更新しました。');
    }

    public function resetAllStudentPoints(Request $request): RedirectResponse
    {
        $teacher = $request->user();

        DB::transaction(function () use ($teacher) {
            User::query()
                ->where('role', User::ROLE_STUDENT)
                ->update([
                    'total_points' => 0,
                    'points_reset_at' => now(),
                    'updated_at' => now(),
                ]);

            $setting = PointResetSetting::query()->first() ?? new PointResetSetting();
            $setting->last_reset_at = now();
            $setting->updated_by = $teacher->id;
            $setting->save();
        });

        return back()->with('success', '全生徒のポイントをリセットしました。');
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
        $setting = PointResetSetting::query()->first();

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

        DB::transaction(function () use ($setting, $teacher) {
            User::query()
                ->where('role', User::ROLE_STUDENT)
                ->update([
                    'total_points' => 0,
                    'points_reset_at' => now(),
                    'updated_at' => now(),
                ]);

            $setting->last_reset_at = now();
            $setting->updated_by = $teacher->id;
            $setting->save();
        });
    }
}
