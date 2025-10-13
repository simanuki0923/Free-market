<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sell_id')->constrained('sells')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id','sell_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('purchases'); }
};
