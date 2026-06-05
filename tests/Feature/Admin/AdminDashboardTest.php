<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_active_users_and_management_links(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'last_login_at' => now()->subDay(),
        ]);

        $activeUser = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Active Student',
            'last_login_at' => now()->subMonths(2),
        ]);

        $inactiveUser = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'name' => 'Inactive Student',
            'last_login_at' => now()->subMonths(7),
        ]);

        $neverLoggedInUser = User::factory()->create([
            'role' => User::ROLE_TEACHER,
            'name' => 'Never Login Teacher',
            'last_login_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('試験管理');
        $response->assertSee('問題管理');
        $response->assertSee('ユーザー管理');
        $response->assertSee($activeUser->name);
        $response->assertDontSee($inactiveUser->name);
        $response->assertDontSee($neverLoggedInUser->name);
    }
}