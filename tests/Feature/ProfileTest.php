<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_teacher_can_update_subject_name_from_profile(): void
    {
        $teacher = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'subject_name' => '数学',
            'position' => '教諭',
        ]);

        $response = $this
            ->actingAs($teacher)
            ->patch('/profile', [
                'name' => 'Teacher User',
                'email' => 'teacher-profile@example.com',
                'subject_name' => '英語',
                'position' => '主任教諭',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $teacher->refresh();

        $this->assertSame('英語', $teacher->subject_name);
        $this->assertSame('主任教諭', $teacher->position);
    }

    public function test_student_cannot_update_subject_name_from_profile(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'subject_name' => null,
            'position' => null,
        ]);

        $response = $this
            ->actingAs($student)
            ->patch('/profile', [
                'name' => 'Student User',
                'email' => 'student-profile@example.com',
                'subject_name' => '理科',
                'position' => '教諭',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $student->refresh();

        $this->assertNull($student->subject_name);
        $this->assertNull($student->position);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
