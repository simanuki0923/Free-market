<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_update_basic_fields_and_redirects_to_mypage(): void
    {
        $user = User::factory()->create(['name' => '旧ユーザー名']);
        $profile = Profile::factory()->for($user)->create([
            'postal_code' => '111-1111',
            'address1'    => '旧住所',
            'address2'    => '旧建物',
        ]);

        $this->actingAs($user);

        $payload = [
            '_method'           => 'PATCH',
            'display_name'      => '新しい名前',
            'postal_code'       => '123-4567',
            'address'           => '東京都新宿区',
            'address1'          => '東京都新宿区',
            'address_pref_city' => '東京都新宿区',
            'building_name'     => 'サンプルビル101',
            'address2'          => 'サンプルビル101',
        ];

        $response = $this->from(route('mypage.profile'))
                         ->post(route('profile.update'), $payload);

        $response->assertRedirect('/mypage');
        $response->assertSessionHas('status', 'プロフィールを更新しました。');

        $user->refresh();
        $profile->refresh();

        $this->assertSame('新しい名前', $user->name);
        $this->assertSame('123-4567', $profile->postal_code);
        $this->assertSame('東京都新宿区', $profile->address1);
        $this->assertSame('サンプルビル101', $profile->address2);
    }

    #[Test]
    public function first_time_setup_with_verified_email_redirects_to_root(): void
    {
        $user = User::factory()->create([
            'name'              => '元の名前',
            'email_verified_at' => now(),
        ]);

        $this->assertNull($user->profile);
        $this->actingAs($user);

        $payload = [
            '_method'           => 'PATCH',
            'display_name'      => '初回設定後の名前',
            'postal_code'       => '987-6543',
            'address'           => '東京都渋谷区',
            'address1'          => '東京都渋谷区',
            'address_pref_city' => '東京都渋谷区',
        ];

        $response = $this->from(route('mypage.profile'))
                         ->post(route('profile.update'), $payload);

        $response->assertRedirect('/');
        $response->assertSessionHas('status', 'プロフィールを更新しました。');

        $user->refresh();
        $this->assertSame('初回設定後の名前', $user->name);
        $this->assertNotNull($user->profile);
        $this->assertSame('987-6543', $user->profile->postal_code);
        $this->assertSame('東京都渋谷区', $user->profile->address1);
    }

    #[Test]
    public function updating_icon_replaces_file_and_deletes_old_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create([
            'icon_image_path' => 'profile_icons/old.png',
        ]);

        Storage::disk('public')->put($profile->icon_image_path, 'OLD');

        $this->actingAs($user);

        $newIcon = UploadedFile::fake()->create('newicon.png', 10, 'image/png');

        $payload = [
            '_method'           => 'PATCH',
            'display_name'      => '名前',
            'postal_code'       => '123-4567',
            'address'           => '東京都千代田区',
            'address1'          => '東京都千代田区',
            'address_pref_city' => '東京都千代田区',
            'icon'              => $newIcon,
        ];

        $response = $this->post(route('profile.update'), $payload);
        $response->assertRedirect('/mypage');

        $profile->refresh();
        $this->assertNotNull($profile->icon_image_path);
        $this->assertStringStartsWith('profile_icons/', $profile->icon_image_path);

        Storage::disk('public')->assertExists($profile->icon_image_path);
        Storage::disk('public')->assertMissing('profile_icons/old.png');
    }

    #[Test]
    public function validation_error_redirects_back_with_old_input(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();
        $this->actingAs($user);

        $tooLong = str_repeat('あ', 21);

        $response = $this->from(route('mypage.profile'))->post(route('profile.update'), [
            '_method'      => 'PATCH',
            'display_name' => $tooLong,
        ]);

        $response->assertStatus(302)->assertRedirect(route('mypage.profile'));

        $follow = $this->get(route('mypage.profile'))->assertOk();
        $follow->assertSee('value="'.$tooLong.'"', false);
    }
}
