<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required_and_validation_message_is_shown(): void
    {
        $response = $this->post(route('login'), [
            'email'    => '',
            'password' => 'dummy-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required_and_validation_message_is_shown(): void
    {
        $response = $this->post(route('login'), [
            'email'    => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_invalid_credentials_shows_global_auth_error_message(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email'    => 'not-registered@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'auth' => 'ログイン情報が登録されていません',
            ]);
    }
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'valid@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email'    => 'valid@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertStatus(302);
    }
}
