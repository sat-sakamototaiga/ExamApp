<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
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

        $assignedStudentIds = $teacher->students()->pluck('users.id');

        $students = User::query()
            ->whereIn('id', $assignedStudentIds)
            ->leftJoin('exam_results', 'users.id', '=', 'exam_results.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COALESCE(SUM(exam_results.score), 0) as total_score'),
                DB::raw('COALESCE(SUM(exam_results.question_count), 0) as total_questions')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
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

        return view('teacher.student-progress', compact('students', 'feedbackByStudent'));
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
}
