<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ログイン確認用ユーザー（デモユーザー）
        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'              => 'Demo User',
                'password'          => Hash::make('password'),  // パスワード: password
                'email_verified_at' => now(),                   // Fortify等のメール認証を通過させる
                'remember_token'    => Str::random(10),
            ]
        );

        // 追加の出品者ユーザー（ダミー）
        // 既に同メールが存在する場合は Factory がユニークなメールで作成します
        User::factory()
            ->count(3)
            ->create([
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
            ]);
    }
}
