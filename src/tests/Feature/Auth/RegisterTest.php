<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        $base = [
            'name'                  => '山田太郎',
            'email'                 => strtolower('taro'.Str::random(6).'@example.com'),
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ];
        return array_merge($base, $overrides);
    }

    #[Test]
    public function it_shows_error_when_name_is_missing(): void
    {
        $payload = $this->validPayload(['name' => '']);

        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
        $this->assertStringContainsString('お名前を入力してください',
            collect(session('errors')->get('name'))->implode(' / ')
        );
    }

    #[Test]
    public function it_shows_error_when_email_is_missing(): void
    {
        $payload = $this->validPayload(['email' => '']);

        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('メールアドレスを入力してください',
            collect(session('errors')->get('email'))->implode(' / ')
        );
    }

    #[Test]
    public function it_shows_error_when_password_is_missing(): void
    {
        $payload = $this->validPayload(['password' => '', 'password_confirmation' => '' ]);

        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertStringContainsString('パスワードを入力してください',
            collect(session('errors')->get('password'))->implode(' / ')
        );
    }

    #[Test]
    public function it_shows_error_when_password_is_too_short(): void
    {
        $payload = $this->validPayload([
            'password' => 'short7',
            'password_confirmation' => 'short7',
        ]);

        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertStringContainsString('パスワードは8文字以上で入力してください',
            collect(session('errors')->get('password'))->implode(' / ')
        );
    }

    #[Test]
    public function it_shows_error_when_password_confirmation_mismatch(): void
    {
        $payload = $this->validPayload([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');
        $this->assertStringContainsString('パスワードと一致しません',
            collect(session('errors')->get('password'))->implode(' / ')
        );
    }

    #[Test]
    public function it_registers_user_and_redirects_to_email_verification_notice(): void
    {
        $payload = $this->validPayload();

        $response = $this->post(route('register'), $payload);

        $this->assertDatabaseHas('users', ['email' => strtolower($payload['email'])]);

        $this->assertAuthenticated();

        $response->assertRedirect(route('verification.notice'));
    }
}
