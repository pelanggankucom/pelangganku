<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('inactive'); // inactive | pending | active | expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('doku_invoice_number')->nullable()->unique();
            $table->string('doku_payment_url', 512)->nullable();
            $table->integer('amount')->default(25000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_subscriptions');
    }
};
