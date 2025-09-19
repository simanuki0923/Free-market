<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // 決済・配送（あなたの既存案の項目を “create” 化）
            $table->string('payment_method', 32)->nullable();
            $table->string('status', 16)->default('pending');   // pending/paid/shipped 等
            $table->string('stripe_session_id', 255)->nullable();

            $table->string('shipping_recipient', 100)->nullable();
            $table->string('shipping_postal_code', 16)->nullable();
            $table->string('shipping_prefecture', 50)->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_address_line1', 255)->nullable();
            $table->string('shipping_address_line2', 255)->nullable();
            $table->string('shipping_phone', 32)->nullable();

            $table->timestamps();
            $table->index(['status','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchases');
    }
};

