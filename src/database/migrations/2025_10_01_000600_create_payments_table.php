<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->enum('payment_method', ['convenience_store','credit_card','bank_transfer'])->index();
            $t->string('provider_txn_id')->nullable(); // Stripe等の取引ID
            $t->unsignedInteger('paid_amount');
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
