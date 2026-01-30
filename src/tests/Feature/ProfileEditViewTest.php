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

        Storage::disk('public')->put($profile->icon_image_path, 'dummy');

        $this->actingAs($user);

        $response = $this->get(route('mypage.profile'))->assertOk();

        $response->assertSee('storage/'.$profile->icon_image_path, false);

        $response->assertSee('name="display_name"', false)
                 ->assertSee('value="山田太郎"', false);

        $response->assertSee('name="postal_code"', false)
                 ->assertSee('value="123-4567"', false);

        $response->assertSee('name="address_pref_city"', false)
                 ->assertSee('value="東京都新宿区"', false);

        $response->assertSee('name="building_name"', false)
                 ->assertSee('value="サンプルビル101"', false);
    }

    #[Test]
    public function edit_page_uses_default_icon_when_profile_icon_is_absent(): void
    {
        $user = User::factory()->create(['name' => '田中花子']);

        Profile::factory()->for($user)->create([
            'icon_image_path' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('mypage.profile'))->assertOk();

        $response->assertSee('/img/sample.jpg', false);
    }

    #[Test]
    public function old_input_has_priority_when_validation_error_occurs(): void
    {
        $user = User::factory()->create(['name' => '元の名前']);
        Profile::factory()->for($user)->create();

        $this->actingAs($user);

        $tooLongName = str_repeat('あ', 21);

        $response = $this->from(route('mypage.profile'))->post(route('profile.update'), [
            '_method'            => 'PATCH',
            'display_name'       => $tooLongName,
            'postal_code'        => '123-4567',
            'address_pref_city'  => '東京都千代田区',
        ]);

        $response->assertStatus(302)->assertRedirect(route('mypage'));
        $follow = $this->get(route('mypage.profile'))->assertOk();
        $follow->assertSee('value="'.$tooLongName.'"', false);

    }
}
