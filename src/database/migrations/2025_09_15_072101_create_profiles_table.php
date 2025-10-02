<?php
// database/migrations/2025_10_02_000000_create_profiles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            // 各ユーザー1件のプロフィール（users 1:1 profiles）
            $table->foreignId('user_id')
                ->constrained()                 // ->references('id')->on('users')
                ->cascadeOnDelete()             // ユーザー削除時にプロフィールも削除
                ->unique();                     // 同一ユーザーで重複作成させない

            // 住所系
            $table->string('postal_code', 16)->nullable();
            $table->string('address1')->nullable();     // 都道府県・市区町村・番地など
            $table->string('address2')->nullable();     // 建物名・部屋番号など
            $table->string('phone', 50)->nullable();

            // プロフィールアイコン（storage/app/public/... or 直接URL）
            $table->string('icon_image_path')->nullable();

            $table->timestamps();

            // よく使う検索に備えてインデックス（任意）
            $table->index('postal_code');
            $table->index('phone');
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
