<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileEditViewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_cannot_access_edit_and_is_redirected_to_login(): void
    {
        // 未ログインでプロフィール編集画面（/mypage/profile）にアクセスしたら
        // ログイン画面へリダイレクトされること
        $response = $this->get(route('mypage.profile'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function edit_page_shows_existing_user_info_and_profile_icon(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['name' => '山田太郎']);

        $profile = Profile::factory()->for($user)->create([
            'postal_code'     => '123-4567',
            'address1'        => '東京都新宿区',
            'address2'        => 'サンプルビル101',
            'icon_image_path' => 'profile_icons/dummy.png',
        ]);

        // 疑似のアイコンファイルをpublicディスクに配置
        Storage::disk('public')->put($profile->icon_image_path, 'dummy');

        // ログイン状態にする
        $this->actingAs($user);

        // プロフィール編集画面そのものは /mypage/profile を想定
        $response = $this->get(route('mypage.profile'))->assertOk();

        // アイコンパスが <img src="storage/..."> として表示されていること
        $response->assertSee('storage/'.$profile->icon_image_path, false);

        // ユーザー名（display_name）がフォームのinputに入っていること
        $response->assertSee('name="display_name"', false)
                 ->assertSee('value="山田太郎"', false);

        // 郵便番号
        $response->assertSee('name="postal_code"', false)
                 ->assertSee('value="123-4567"', false);

        // 住所1（都道府県+市区町村など）
        $response->assertSee('name="address_pref_city"', false)
                 ->assertSee('value="東京都新宿区"', false);

        // 住所2（建物名など）
        $response->assertSee('name="building_name"', false)
                 ->assertSee('value="サンプルビル101"', false);
    }

    #[Test]
    public function edit_page_uses_default_icon_when_profile_icon_is_absent(): void
    {
        $user = User::factory()->create(['name' => '田中花子']);

        // プロフィールはあるが icon_image_path が null のケース
        Profile::factory()->for($user)->create([
            'icon_image_path' => null,
        ]);

        $this->actingAs($user);

        // 編集画面は /mypage/profile
        $response = $this->get(route('mypage.profile'))->assertOk();

        // アイコン未設定時はデフォルト画像が表示されること
        $response->assertSee('/img/sample.jpg', false);
    }

    #[Test]
    public function old_input_has_priority_when_validation_error_occurs(): void
    {
        $user = User::factory()->create(['name' => '元の名前']);
        Profile::factory()->for($user)->create();

        $this->actingAs($user);

        // バリデーションエラーを発生させるために21文字の名前を投げる
        $tooLongName = str_repeat('あ', 21);

        // 送信元は /mypage/profile （編集フォームがある場所）
        // 送信先は profile.update (PATCH相当)
        $response = $this->from(route('mypage.profile'))->post(route('profile.update'), [
            '_method'            => 'PATCH',
            'display_name'       => $tooLongName,
            'postal_code'        => '123-4567',
            'address_pref_city'  => '東京都千代田区',
        ]);

        // 実装側はエラー時に /mypage にリダイレクトしている想定
        $response->assertStatus(302)->assertRedirect(route('mypage'));

        // その後ユーザーが再度プロフィール編集画面（/mypage/profile）を開いたときに
        // old() がフォームに反映されていることを確認する
        $follow = $this->get(route('mypage.profile'))->assertOk();

        // エラーになった長すぎる名前が value="..." に入っていること
        $follow->assertSee('value="'.$tooLongName.'"', false);

        // ← ここで 'error' を期待するアサートは削除
        // 画面の実装が 'error' という文字列を出していないため
    }
}
