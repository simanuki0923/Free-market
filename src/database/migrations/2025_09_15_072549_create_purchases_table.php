<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // 金額：小数で保存（整数で良ければ unsignedInteger に変更OK）
            $table->decimal('price', 8, 2);

            // 追加項目（★ after(...) は書かない）
            $table->string('payment_method', 32)->nullable(); // 'credit_card' など
            $table->string('status', 16)->default('pending'); // 決済後は 'paid' を保存
            $table->timestamp('purchase_date')->nullable();

            $table->timestamps();

            // 索引
            $table->index(['status', 'created_at']);
            $table->index('user_id');
            // 1商品1回しか売らないなら有効化
            // $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
}
