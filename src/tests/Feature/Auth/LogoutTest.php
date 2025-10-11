<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト内容：ログアウトができる
     * テスト手順：ユーザーにログインをする → ログアウトボタンを押す（= /logout に POST）
     * 期待挙動：ログアウト処理が実行される（= 非認証状態になり 302 リダイレクト）
     */
    public function test_user_can_logout(): void
    {
        // 1) ログイン状態を作る
        $user = User::factory()->create();
        $this->actingAs($user);

        // 2) CSRF トークンを付けて Fortify の logout へ POST
        $token = csrf_token();
        $response = $this->post(route('logout'), ['_token' => $token]);

        // 3) 期待挙動：非ログイン（ゲスト）になっている & リダイレクト
        $response->assertStatus(302);
        $this->assertGuest();
    }
}
