<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->unsignedInteger('price');
            $table->string('image_path')->nullable();
            $table->string('condition', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
