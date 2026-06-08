<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_a_teacher_from_user_list(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Teacher One',
            'email' => 'teacher1@example.com',
            'role' => User::ROLE_TEACHER,
            'subject_name' => '数学',
            'position' => '教諭',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Teacher One',
            'email' => 'teacher1@example.com',
            'role' => User::ROLE_TEACHER,
            'subject_name' => '数学',
            'position' => '教諭',
        ]);
    }

    public function test_admin_can_import_users_from_csv(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $csv = implode("\n", [
            '名前,メールアドレス,ロール,教科名,役職,パスワード',
            'Teacher Two,teacher2@example.com,teacher,理科,主任教諭,password123',
            'Student Two,student2@example.com,student,,,password123',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', "\xEF\xBB\xBF{$csv}");

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'teacher2@example.com',
            'role' => User::ROLE_TEACHER,
            'subject_name' => '理科',
            'position' => '主任教諭',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'student2@example.com',
            'role' => User::ROLE_STUDENT,
            'subject_name' => null,
            'position' => null,
        ]);
    }

    public function test_admin_cannot_register_teacher_without_subject_name(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(route('admin.users.store'), [
            'name' => 'Teacher No Subject',
            'email' => 'teacher-no-subject@example.com',
            'role' => User::ROLE_TEACHER,
            'subject_name' => '',
            'position' => '教諭',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHasErrors('subject_name');

        $this->assertDatabaseMissing('users', [
            'email' => 'teacher-no-subject@example.com',
        ]);
    }
}