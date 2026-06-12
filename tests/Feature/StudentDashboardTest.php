<?php

namespace Tests\Feature;

use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_displays_subject_rankings_with_teacher_subjects(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Target Student',
            'total_points' => 80,
        ]);

        $mathTopStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Math Top Student',
            'total_points' => 100,
        ]);

        $commonStudent = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Common Student',
            'total_points' => 60,
        ]);

        $mathTeacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'subject_name' => '数学',
        ]);

        $englishTeacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'subject_name' => '英語',
        ]);

        $mathTeacher->students()->attach([$student->id, $mathTopStudent->id, $commonStudent->id]);
        $englishTeacher->students()->attach([$student->id, $commonStudent->id]);

        PointHistory::create([
            'user_id' => $student->id,
            'teacher_id' => $mathTeacher->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 30,
            'balance_after' => 80,
        ]);
        PointHistory::create([
            'user_id' => $mathTopStudent->id,
            'teacher_id' => $mathTeacher->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 50,
            'balance_after' => 100,
        ]);
        PointHistory::create([
            'user_id' => $commonStudent->id,
            'teacher_id' => $mathTeacher->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 10,
            'balance_after' => 60,
        ]);

        PointHistory::create([
            'user_id' => $student->id,
            'teacher_id' => $englishTeacher->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 40,
            'balance_after' => 80,
        ]);
        PointHistory::create([
            'user_id' => $commonStudent->id,
            'teacher_id' => $englishTeacher->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 20,
            'balance_after' => 60,
        ]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('自分の所持ポイント');
        $response->assertSee('数学');
        $response->assertSee('英語');
        $response->assertSee('3名中 2位');
        $response->assertSee('2名中 1位');
        $response->assertSee('Math Top Student');
        $response->assertSee('Target Student');
    }
}
