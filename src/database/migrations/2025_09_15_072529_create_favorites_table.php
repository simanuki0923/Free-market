<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id','product_id']);         // 二重いいね防止
            $table->timestamps();
            $table->index(['user_id','created_at']);
            $table->index(['product_id','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('favorites');
    }
};

