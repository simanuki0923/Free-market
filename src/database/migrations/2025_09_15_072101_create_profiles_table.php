<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            // 1:1 の関連（ユーザー1人につきプロフィール1つ）
            $table->foreignId('user_id')
                ->unique()                         // 同一ユーザーで重複作成させない
                ->constrained()                    // users.id 参照
                ->cascadeOnDelete();               // ユーザー削除でプロフも削除
            $table->string('display_name', 255)->nullable();
            $table->string('icon_image_path', 2048)->nullable();
            $table->string('postal_code', 16)->nullable()->index();
            $table->string('address', 255)->nullable();
            $table->string('building_name', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
