<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            // MySQL 5.7系でのインデックス長問題を避けたい場合は 191 にしてもOK
            $table->string('email', 255)->unique();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password'); // ハッシュ保存（bcrypt/argon）

            $table->rememberToken(); // remember me
            $table->timestamps();

            $table->index('created_at'); // 任意だが一覧などで便利
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
