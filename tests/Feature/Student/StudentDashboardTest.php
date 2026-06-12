<?php

namespace Tests\Feature\Student;

use App\Models\Exam;
use App\Models\PointHistory;
use App\Models\Question;
use App\Models\QuestionAnswerLog;
use App\Models\TeacherFeedbackComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_displays_latest_feedback_points_rank_and_weak_questions(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => '担任A',
        ]);

        $secondTeacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => '担任B',
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'total_points' => 12,
        ]);

        $highRankStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '上位生徒',
            'total_points' => 20,
        ]);

        $lowRankStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '下位生徒',
            'total_points' => 5,
        ]);

        $unrankedStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '未ランク生徒',
            'total_points' => 0,
        ]);

        $outsideStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '対象外生徒',
            'total_points' => 999,
        ]);

        $globalOutOfRangeStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '全体圏外生徒',
            'total_points' => 1,
        ]);

        $teacher->students()->attach([$student->id, $highRankStudent->id, $lowRankStudent->id]);
        $secondTeacher->students()->attach([$student->id, $unrankedStudent->id]);

        $oldFeedback = TeacherFeedbackComment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'comment' => '古いコメント',
        ]);
        $oldFeedback->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->save();

        $latestFeedback = TeacherFeedbackComment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'comment' => '最新コメント',
        ]);
        $latestFeedback->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        $exam = Exam::create(['name' => '確認試験']);

        $question1 = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $teacher->id,
            'question_text' => '苦手問題1',
            'difficulty' => Question::DIFFICULTY_NORMAL,
        ]);
        $question2 = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $teacher->id,
            'question_text' => '苦手問題2',
            'difficulty' => Question::DIFFICULTY_NORMAL,
        ]);
        $question3 = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $teacher->id,
            'question_text' => '苦手問題3',
            'difficulty' => Question::DIFFICULTY_NORMAL,
        ]);

        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question1->id, 'is_correct' => false]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question1->id, 'is_correct' => false]);

        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question2->id, 'is_correct' => true]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question2->id, 'is_correct' => false]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question2->id, 'is_correct' => false]);

        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question3->id, 'is_correct' => true]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $question3->id, 'is_correct' => false]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('担任フィードバックコメント');
        $response->assertSee('最新コメント');
        $response->assertDontSee('古いコメント');
        $response->assertSee('12');
        $response->assertSee('現在順位（全生徒）');
        $response->assertSee('3 位');
        $response->assertSee('正答率が低い問題（ランダム3問）');
        $response->assertSee('全生徒ポイントランキング');
        $response->assertSee('教科別ポイントランキング');
        $response->assertSee('担任A 先生');
        $response->assertSee('担任B 先生');
        $response->assertSee($outsideStudent->name);
        $response->assertSee($highRankStudent->name);
        $response->assertSee($lowRankStudent->name);
        $response->assertSee($unrankedStudent->name);
        $response->assertDontSee($globalOutOfRangeStudent->name);
        $response->assertSee('- 位');
        $response->assertSee('苦手問題1');
        $response->assertSee('苦手問題2');
        $response->assertSee('苦手問題3');
    }

    public function test_student_dashboard_shows_dash_rank_when_points_are_zero(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'total_points' => 0,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('- 位');
    }

    public function test_student_can_open_feedback_history_page(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        TeacherFeedbackComment::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'comment' => '履歴コメント',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.feedback-history'));

        $response->assertOk();
        $response->assertSee('フィードバック履歴');
        $response->assertSee('履歴コメント');
    }

    public function test_teacher_based_ranking_uses_dense_rank_when_points_tie(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => '担任A',
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '対象生徒',
            'total_points' => 40,
        ]);

        $samePointStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '同率生徒',
            'total_points' => 40,
        ]);

        $nextPointStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '次点生徒',
            'total_points' => 30,
        ]);

        $teacher->students()->attach([
            $student->id,
            $samePointStudent->id,
            $nextPointStudent->id,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('👑');
        $response->assertSee('1 位', false);
        $response->assertSee('2 位', false);
        $response->assertDontSee('3 位', false);
    }

    public function test_student_dashboard_weak_questions_do_not_include_100_percent_accuracy_questions(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $exam = Exam::create(['name' => '弱点確認']);

        $questionPerfect = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $teacher->id,
            'question_text' => '正答率100問題',
            'difficulty' => Question::DIFFICULTY_NORMAL,
        ]);

        $questionWeak = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $teacher->id,
            'question_text' => '正答率50問題',
            'difficulty' => Question::DIFFICULTY_NORMAL,
        ]);

        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $questionPerfect->id, 'is_correct' => true]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $questionPerfect->id, 'is_correct' => true]);

        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $questionWeak->id, 'is_correct' => true]);
        QuestionAnswerLog::create(['user_id' => $student->id, 'question_id' => $questionWeak->id, 'is_correct' => false]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('正答率50問題');
        $response->assertDontSee('正答率100問題');
    }

    public function test_student_dashboard_appends_current_student_after_top_three_when_outside_top_three(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => '対象生徒',
            'total_points' => 0,
        ]);

        $top1 = User::factory()->create(['role' => User::ROLE_STUDENT, 'total_points' => 100]);
        $top2 = User::factory()->create(['role' => User::ROLE_STUDENT, 'total_points' => 90]);
        $top3 = User::factory()->create(['role' => User::ROLE_STUDENT, 'total_points' => 80]);
        User::factory()->create(['role' => User::ROLE_STUDENT, 'total_points' => 70]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();

        /** @var array<string, mixed> $studentDashboard */
        $studentDashboard = $response->viewData('studentDashboard');
        $displayStudents = $studentDashboard['globalRankingDisplayStudents'];

        $this->assertCount(4, $displayStudents);
        $this->assertSame([$top1->id, $top2->id, $top3->id, $student->id], $displayStudents->pluck('id')->all());
    }

    public function test_student_dashboard_point_history_event_type_is_localized_to_japanese(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'subject_name' => '数学',
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        PointHistory::create([
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'question_id' => null,
            'exam_id' => null,
            'event_type' => PointHistory::EVENT_PERFECT_BONUS,
            'points_delta' => 10,
            'balance_after' => 10,
            'notes' => 'テスト用',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('全問正解ボーナス');
        $response->assertDontSee(PointHistory::EVENT_PERFECT_BONUS);
    }
}
