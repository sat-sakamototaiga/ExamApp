<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\PointHistory;
use App\Models\Question;
use App\Models\QuestionAnswerLog;
use App\Models\User;
use App\Support\PointService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PointDisplayTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->resetPointData();

            [$mathTeacher, $englishTeacher] = $this->resolveTeachers();
            $students = $this->resolveStudents(5);

            $mathTeacher->students()->syncWithoutDetaching([
                $students[0]->id,
                $students[1]->id,
                $students[2]->id,
            ]);
            $englishTeacher->students()->syncWithoutDetaching([
                $students[0]->id,
                $students[3]->id,
                $students[4]->id,
            ]);

            $exam = Exam::query()->firstOrCreate(
                ['name' => '表示確認用ポイントテスト'],
                ['description' => 'ポイント表示確認用に作成した試験データ']
            );

            $mathQuestionNormal = $this->firstOrCreateQuestion($exam->id, $mathTeacher->id, '【表示確認】数学・標準問題', Question::DIFFICULTY_NORMAL);
            $mathQuestionExpert = $this->firstOrCreateQuestion($exam->id, $mathTeacher->id, '【表示確認】数学・応用問題', Question::DIFFICULTY_EXPERT);
            $englishQuestionEasy = $this->firstOrCreateQuestion($exam->id, $englishTeacher->id, '【表示確認】英語・基本問題', Question::DIFFICULTY_EASY);
            $englishQuestionNormal = $this->firstOrCreateQuestion($exam->id, $englishTeacher->id, '【表示確認】英語・標準問題', Question::DIFFICULTY_NORMAL);

            /** @var PointService $pointService */
            $pointService = app(PointService::class);

            // Student A: 数学 +8, 英語 +3 => 合計11
            $pointService->awardQuestionPoints($students[0], $mathQuestionNormal, 3);
            $pointService->awardQuestionPoints($students[0], $mathQuestionExpert, 5);
            $pointService->awardQuestionPoints($students[0], $englishQuestionNormal, 3);

            // Student B: 数学 +5
            $pointService->awardQuestionPoints($students[1], $mathQuestionExpert, 5);

            // Student C: 数学 +1
            $pointService->awardQuestionPoints($students[2], $mathQuestionNormal, 1);

            // Student D: 英語 +13（問題 +3 + 全問ボーナス +10）
            $pointService->awardQuestionPoints($students[3], $englishQuestionNormal, 3);
            $pointService->awardBonusPoints($students[3], 10, $englishTeacher->id, $exam->id);

            // Student E: 英語 +1
            $pointService->awardQuestionPoints($students[4], $englishQuestionEasy, 1);

            ExamResult::query()->create([
                'user_id' => $students[3]->id,
                'exam_id' => $exam->id,
                'score' => 4,
                'question_count' => 4,
                'points_earned' => 13,
                'bonus_points' => 10,
            ]);

            QuestionAnswerLog::query()->create([
                'user_id' => $students[0]->id,
                'question_id' => $mathQuestionNormal->id,
                'is_correct' => true,
            ]);
            QuestionAnswerLog::query()->create([
                'user_id' => $students[0]->id,
                'question_id' => $englishQuestionNormal->id,
                'is_correct' => false,
            ]);
            QuestionAnswerLog::query()->create([
                'user_id' => $students[3]->id,
                'question_id' => $englishQuestionNormal->id,
                'is_correct' => true,
            ]);
        });
    }

    private function resetPointData(): void
    {
        PointHistory::query()->delete();
        QuestionAnswerLog::query()->delete();
        ExamResult::query()->delete();

        User::query()
            ->where('role', User::ROLE_STUDENT)
            ->update([
                'total_points' => 0,
                'points_reset_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function resolveTeachers(): array
    {
        $teachers = User::query()
            ->where('role', User::ROLE_TEACHER)
            ->orderBy('id')
            ->take(2)
            ->get();

        while ($teachers->count() < 2) {
            $index = $teachers->count() + 1;
            $teachers->push(User::query()->create([
                'name' => '表示確認担任' . $index,
                'email' => 'point-display-teacher-' . $index . '@example.test',
                'role' => User::ROLE_TEACHER,
                'subject_name' => $index === 1 ? '数学' : '英語',
                'password' => 'password',
            ]));
        }

        $mathTeacher = $teachers[0];
        $englishTeacher = $teachers[1];

        if (! $mathTeacher->subject_name) {
            $mathTeacher->forceFill(['subject_name' => '数学'])->save();
        }

        if (! $englishTeacher->subject_name) {
            $englishTeacher->forceFill(['subject_name' => '英語'])->save();
        }

        return [$mathTeacher, $englishTeacher];
    }

    /**
     * @return array<int, User>
     */
    private function resolveStudents(int $requiredCount): array
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->orderBy('id')
            ->take($requiredCount)
            ->get();

        while ($students->count() < $requiredCount) {
            $index = $students->count() + 1;
            $students->push(User::query()->create([
                'name' => '表示確認生徒' . $index,
                'email' => 'point-display-student-' . Str::uuid() . '@example.test',
                'role' => User::ROLE_STUDENT,
                'password' => 'password',
            ]));
        }

        return $students->values()->all();
    }

    private function firstOrCreateQuestion(int $examId, int $teacherId, string $questionText, string $difficulty): Question
    {
        return Question::query()->firstOrCreate([
            'exam_id' => $examId,
            'created_by' => $teacherId,
            'question_text' => $questionText,
        ], [
            'difficulty' => $difficulty,
        ]);
    }
}