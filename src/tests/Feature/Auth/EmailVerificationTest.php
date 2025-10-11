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
        // ==== テスト内容1：会員登録後、認証メールが送信される（仕様1） ====
        // 手順：会員登録を行う → 期待：登録アドレス宛に VerifyEmail 通知が送信、/email/verify へ遷移
        // 期待挙動は「テスト対応.txt」の1番参照。 :contentReference[oaicite:2]{index=2}

        Notification::fake();

        $payload  = $this->validRegisterPayload();
        $response = $this->post(route('register'), $payload);

        // /email/verify へ（verification.notice）に誘導
        $response->assertRedirect(route('verification.notice'));

        // 通知（VerifyEmail）が送られていること
        $user = User::where('email', strtolower($payload['email']))->first();
        $this->assertNotNull($user, 'User should be created.');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function verify_notice_page_has_mailto_button(): void
    {
        // ==== テスト内容2：メール認証誘導画面の「認証はこちらから」ボタンでメール認証サイトへ（仕様2） ====
        // 手順：/email/verify を表示 → ボタン（mailto:）を確認
        // 期待挙動：メール認証サイトへ導線があること（mailto:リンクの存在で担保） :contentReference[oaicite:3]{index=3}
        //
        // 現状ビュー：verify-email.blade.php に <a href="mailto:">認証はこちらから</a> が定義されているため、
        // その存在を検証する（auth 必須のためログイン状態でアクセス）。 :contentReference[oaicite:4]{index=4}

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('認証はこちらから')
            // 第二引数 false で HTML をエスケープせず確認
            ->assertSee('href="mailto:"', false);
    }

    #[Test]
    public function email_verification_completes_and_redirects_to_profile(): void
    {
        // ==== テスト内容3：メール認証完了でプロフィール設定画面に遷移（仕様3） ====
        // 手順：署名付きURLで /email/verify/{id}/{hash} にアクセス
        // 期待挙動：ユーザが verified になり、プロフィール画面へリダイレクト。 :contentReference[oaicite:5]{index=5}
        //
        // 実装：VerifyEmailResponse は route('mypage.profile') にリダイレクトする設定。 :contentReference[oaicite:6]{index=6}

        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email), // EmailVerificationRequest が参照
            ]
        );

        $response = $this->get($url);

        $response->assertRedirect(route('mypage.profile')); // 認証完了後の遷移先
        $this->assertTrue($user->fresh()->hasVerifiedEmail(), 'User should be marked as verified.');
    }
}
