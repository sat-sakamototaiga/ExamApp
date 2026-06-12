<?php

namespace Tests\Feature\Teacher;

use App\Models\PointHistory;
use App\Models\PointResetSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPointResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_reset_only_resets_assigned_students_and_scoped_teacher_points(): void
    {
        $teacherA = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => '担任A',
        ]);
        $teacherB = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => '担任B',
        ]);

        $student1 = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'total_points' => 25,
        ]);
        $student2 = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'total_points' => 8,
        ]);
        $student3 = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'total_points' => 12,
        ]);

        $teacherA->students()->attach([$student1->id, $student2->id]);
        $teacherB->students()->attach([$student1->id, $student3->id]);

        PointHistory::create([
            'user_id' => $student1->id,
            'teacher_id' => $teacherA->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 10,
            'balance_after' => 10,
        ]);
        PointHistory::create([
            'user_id' => $student1->id,
            'teacher_id' => $teacherB->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 15,
            'balance_after' => 25,
        ]);
        PointHistory::create([
            'user_id' => $student2->id,
            'teacher_id' => $teacherA->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 5,
            'balance_after' => 5,
        ]);
        PointHistory::create([
            'user_id' => $student3->id,
            'teacher_id' => $teacherB->id,
            'event_type' => PointHistory::EVENT_QUESTION_CORRECT,
            'points_delta' => 12,
            'balance_after' => 12,
        ]);

        $this->actingAs($teacherA)
            ->post(route('teacher.students.points.reset'))
            ->assertRedirect();

        $this->assertSame(15, (int) $student1->fresh()->total_points);
        $this->assertSame(3, (int) $student2->fresh()->total_points);
        $this->assertSame(12, (int) $student3->fresh()->total_points);

        $this->assertDatabaseHas('point_histories', [
            'user_id' => $student1->id,
            'teacher_id' => $teacherA->id,
            'event_type' => PointHistory::EVENT_MANUAL_RESET,
            'points_delta' => -10,
        ]);
        $this->assertDatabaseHas('point_histories', [
            'user_id' => $student2->id,
            'teacher_id' => $teacherA->id,
            'event_type' => PointHistory::EVENT_MANUAL_RESET,
            'points_delta' => -5,
        ]);
        $this->assertDatabaseMissing('point_histories', [
            'user_id' => $student3->id,
            'teacher_id' => $teacherA->id,
            'event_type' => PointHistory::EVENT_MANUAL_RESET,
        ]);

        $this->assertNotNull(PointResetSetting::query()->where('teacher_id', $teacherA->id)->first());
    }
}