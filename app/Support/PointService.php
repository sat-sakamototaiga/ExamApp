<?php

namespace App\Support;

use App\Models\PointHistory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function awardQuestionPoints(User $student, Question $question, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        $teacherId = $this->resolveTeacherId($question->created_by);

        $this->applyPointDelta(
            student: $student,
            pointsDelta: $points,
            eventType: PointHistory::EVENT_QUESTION_CORRECT,
            teacherId: $teacherId,
            questionId: $question->id,
            examId: $question->exam_id,
            notes: '問題正解による加算'
        );
    }

    public function awardBonusPoints(User $student, int $points, ?int $teacherId = null, ?int $examId = null): void
    {
        if ($points <= 0) {
            return;
        }

        $resolvedTeacherId = $this->resolveTeacherId($teacherId);

        $this->applyPointDelta(
            student: $student,
            pointsDelta: $points,
            eventType: PointHistory::EVENT_PERFECT_BONUS,
            teacherId: $resolvedTeacherId,
            questionId: null,
            examId: $examId,
            notes: '全問正解ボーナス'
        );
    }

    public function resetPointsForTeacherStudents(User $teacher, Collection $students, string $eventType): int
    {
        if (! in_array($eventType, [PointHistory::EVENT_MANUAL_RESET, PointHistory::EVENT_AUTO_RESET], true)) {
            throw new \InvalidArgumentException('Unsupported reset event type.');
        }

        $studentIds = $students
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($teacher, $studentIds, $eventType) {
            $lockedStudents = User::query()
                ->whereIn('id', $studentIds)
                ->where('role', User::ROLE_STUDENT)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $resetCount = 0;

            foreach ($studentIds as $studentId) {
                /** @var User|null $student */
                $student = $lockedStudents->get($studentId);
                if ($student === null) {
                    continue;
                }

                $scopedBalance = (int) PointHistory::query()
                    ->where('user_id', $student->id)
                    ->where('teacher_id', $teacher->id)
                    ->sum('points_delta');

                if ($scopedBalance <= 0) {
                    continue;
                }

                $currentTotalPoints = max(0, (int) $student->total_points);
                $resetAmount = min($scopedBalance, $currentTotalPoints);

                if ($resetAmount <= 0) {
                    continue;
                }

                $newTotalPoints = $currentTotalPoints - $resetAmount;

                $student->forceFill([
                    'total_points' => $newTotalPoints,
                    'points_reset_at' => now(),
                ])->save();

                PointHistory::create([
                    'user_id' => $student->id,
                    'teacher_id' => $teacher->id,
                    'question_id' => null,
                    'exam_id' => null,
                    'event_type' => $eventType,
                    'points_delta' => -$resetAmount,
                    'balance_after' => $newTotalPoints,
                    'notes' => '担任単位リセット',
                ]);

                $resetCount++;
            }

            return $resetCount;
        });
    }

    private function applyPointDelta(
        User $student,
        int $pointsDelta,
        string $eventType,
        ?int $teacherId,
        ?int $questionId,
        ?int $examId,
        string $notes
    ): void {
        DB::transaction(function () use ($student, $pointsDelta, $eventType, $teacherId, $questionId, $examId, $notes) {
            $lockedStudent = User::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            $currentTotalPoints = max(0, (int) $lockedStudent->total_points);
            $newTotalPoints = max(0, $currentTotalPoints + $pointsDelta);

            $lockedStudent->forceFill([
                'total_points' => $newTotalPoints,
            ])->save();

            PointHistory::create([
                'user_id' => $lockedStudent->id,
                'teacher_id' => $teacherId,
                'question_id' => $questionId,
                'exam_id' => $examId,
                'event_type' => $eventType,
                'points_delta' => $pointsDelta,
                'balance_after' => $newTotalPoints,
                'notes' => $notes,
            ]);
        });
    }

    private function resolveTeacherId(?int $teacherId): ?int
    {
        if ($teacherId === null) {
            return null;
        }

        $isTeacher = User::query()
            ->whereKey($teacherId)
            ->where('role', User::ROLE_TEACHER)
            ->exists();

        return $isTeacher ? $teacherId : null;
    }
}