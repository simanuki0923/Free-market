<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // 出品者
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // product と1:1紐付け可
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->unsignedInteger('price');
            $table->string('image_path')->nullable();
            $table->string('condition', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
            $table->index(['user_id','category_id']);
            $table->index('price');
            $table->index('is_sold');
        });
    }
    public function down(): void { Schema::dropIfExists('sells'); }
};
