<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();   // 購入者
            $t->foreignId('sell_id')->constrained('sells')->cascadeOnDelete(); // どの出品を購入したか
            $t->unsignedInteger('amount');           // 購入金額
            $t->timestamp('purchased_at')->useCurrent();
            $t->timestamps();

            $t->unique(['user_id','sell_id']); // 同一ユーザーの重複購入防止（不要なら削除）
            $t->index('purchased_at');
        });
    }
    public function down(): void { Schema::dropIfExists('purchases'); }
};
