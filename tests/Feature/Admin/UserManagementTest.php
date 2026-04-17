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
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Teacher One',
            'email' => 'teacher1@example.com',
            'role' => User::ROLE_TEACHER,
        ]);
    }

    public function test_admin_can_import_users_from_csv(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $csv = implode("\n", [
            '名前,メールアドレス,ロール,パスワード',
            'Teacher Two,teacher2@example.com,teacher,password123',
            'Student Two,student2@example.com,student,password123',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', "\xEF\xBB\xBF{$csv}");

        $response = $this->actingAs($admin)->post(route('admin.users.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'teacher2@example.com',
            'role' => User::ROLE_TEACHER,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'student2@example.com',
            'role' => User::ROLE_STUDENT,
        ]);
    }
}