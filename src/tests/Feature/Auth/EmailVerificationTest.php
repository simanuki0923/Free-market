<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function validRegisterPayload(array $overrides = []): array
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
    public function registration_sends_verification_email_and_redirects_to_notice(): void
    {
        Notification::fake();

        $payload  = $this->validRegisterPayload();
        $response = $this->post(route('register'), $payload);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', strtolower($payload['email']))->first();
        $this->assertNotNull($user, 'User should be created.');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function verify_notice_page_has_mailto_button(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('認証はこちらから')
            ->assertSee('href="mailto:"', false);
    }

    #[Test]
    public function email_verification_completes_and_redirects_to_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($url);

        $response->assertRedirect(route('mypage.profile'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail(), 'User should be marked as verified.');
    }
}
