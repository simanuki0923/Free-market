<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Profile;
use App\Models\User;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'icon_image_path' => null,                      // 画像未設定時は blade 側のフォールバック想定
            // 他に項目があればここに追加（display_name, postal_code など）
        ];
    }
}
