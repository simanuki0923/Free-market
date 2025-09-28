<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sells', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name', 255);
            $table->string('brand', 255)->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('image_path', 1024)->nullable();
            $table->unsignedTinyInteger('condition')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_sold')->default(false);

            $table->timestamps();

            $table->index(['user_id','is_sold']);
            $table->index('price');
        });
    }
    public function down(): void { Schema::dropIfExists('sells'); }
};
