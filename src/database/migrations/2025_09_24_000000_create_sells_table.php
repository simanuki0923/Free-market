<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sells', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); // 出品者
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // product と1:1紐付け可
            $t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('brand')->nullable();
            $t->unsignedInteger('price');
            $t->string('image_path')->nullable();
            $t->string('condition', 50)->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_sold')->default(false);
            $t->timestamps();

            $t->index(['user_id','category_id']);
            $t->index('price');
            $t->index('is_sold');
        });
    }
    public function down(): void { Schema::dropIfExists('sells'); }
};
