<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_welcome_screen(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('資格試験対策アプリへようこそ！');
        $response->assertSee('ログインして開始する');
    }

    public function test_authenticated_user_is_redirected_from_welcome_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
