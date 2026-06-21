<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | active | expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('doku_invoice_number')->nullable();
            $table->string('doku_payment_url')->nullable();
            $table->unsignedInteger('amount')->default(25000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_subscriptions');
    }
};
