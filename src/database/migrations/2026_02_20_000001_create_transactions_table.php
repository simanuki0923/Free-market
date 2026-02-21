<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // 既存購入レコードと1:1（1購入 = 1取引想定）
            $table->foreignId('purchase_id')->unique()->constrained()->cascadeOnDelete();

            $table->foreignId('sell_id')->constrained('sells')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();

            // ongoing / buyer_completed / completed / canceled
            $table->string('status', 30)->default('ongoing')->index();

            // サイドバー並び順用（新規メッセージ順）
            $table->timestamp('last_message_at')->nullable()->index();

            // 購入者の完了押下時刻
            $table->timestamp('buyer_completed_at')->nullable();

            // 取引最終完了時刻（両者評価後など）
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['seller_id', 'buyer_id']);
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};