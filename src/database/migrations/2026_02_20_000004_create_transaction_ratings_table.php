<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();

            // 評価した人 / された人
            $table->foreignId('rater_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('comment', 500)->nullable();

            $table->timestamp('rated_at')->nullable();
            $table->timestamps();

            // 同一取引で同一ユーザーの重複評価防止
            $table->unique(['transaction_id', 'rater_user_id']);

            $table->index(['ratee_user_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_ratings');
    }
};