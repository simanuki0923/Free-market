<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 出品者
            $table->string('name', 255);
            $table->string('brand', 255)->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image_url', 2048)->nullable();
            // 初期段階は nullable（後で NOT NULL に引き締め可能）
            $table->string('condition', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sold')->default(false)->index();
            $table->timestamps();
            $table->index(['user_id', 'is_sold']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('products');
    }
};

