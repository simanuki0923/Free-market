<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1) テスト内容：
     *   メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     *    - 手順：ログインページにて email 未入力、password のみ入力 → 送信
     *    - 期待： 「メールアドレスを入力してください」
     *
     *   ※ 現状の LoginRequest では 'メールアドレス必須' を返す設定のため、
     *     下記の期待文言に合わせる場合は messages() の文言を変更してください。
     */
    public function test_email_is_required_and_validation_message_is_shown(): void
    {
        $response = $this->post(route('login'), [
            'email'    => '',
            'password' => 'dummy-password',
        ]);

        // セッションに email のバリデーションエラーが格納される想定
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 2) テスト内容：
     *   パスワードが入力されていない場合、バリデーションメッセージが表示される
     *    - 手順：ログインページにて password 未入力、email のみ入力 → 送信
     *    - 期待： 「パスワードを入力してください」
     *
     *   ※ 現状の LoginRequest では 'パスワード必須' を返す設定。
     */
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

    /**
     * 3) テスト内容：
     *   入力情報が間違っている場合、バリデーションメッセージが表示される
     *    - 手順：未登録のメール/パスワードで送信
     *    - 期待： 「ログイン情報が登録されていません」
     *
     *   備考：
     *   - 現行の Fortify 既定挙動では、認証失敗時に username(email) キーへ
     *     エラーを付与するため、Blade 側で $errors->has('auth') を表示するには
     *     失敗レスポンスを 'auth' キーに乗せる実装が必要です。
     *   - 本テストは指示どおり 'auth' キー＆指定メッセージを前提としています。
     */
    public function test_invalid_credentials_shows_global_auth_error_message(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email'    => 'not-registered@example.com',
            'password' => 'wrong-password',
        ]);

        // 'auth' キーに全体エラーを積む想定の振る舞いを検証
        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'auth' => 'ログイン情報が登録されていません',
            ]);
    }

    /**
     * 4) テスト内容：
     *   正しい情報が入力された場合、ログイン処理が実行される
     *    - 手順：登録済みユーザーの正しい資格情報で送信
     *    - 期待： ログイン成功（認証済み）でリダイレクト
     *
     *   備考：
     *   - リダイレクト先は Fortify の LoginResponse 実装に依存するため
     *     ここでは「リダイレクトであること・認証済みであること」を主に検証。
     */
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
        $response->assertStatus(302); // どこかへリダイレクト（LoginResponse 依存）
    }
}
