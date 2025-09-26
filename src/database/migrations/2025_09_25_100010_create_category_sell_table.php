<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('category_sell', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sell_id')->constrained('sells')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id','sell_id']);
            $table->index('sell_id');
            $table->index('category_id');
        });
    }
    public function down(): void { Schema::dropIfExists('category_sell'); }
};
