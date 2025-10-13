<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $token = csrf_token();
        $response = $this->post(route('logout'), ['_token' => $token]);

        $response->assertStatus(302);
        $this->assertGuest();
    }
}
