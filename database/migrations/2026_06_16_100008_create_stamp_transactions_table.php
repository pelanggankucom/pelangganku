<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // kasir yang melakukan
            $table->string('type'); // earn | redeem | void
            $table->integer('stamps_delta'); // + untuk earn, - untuk redeem
            $table->unsignedBigInteger('reward_id')->nullable(); // jika redeem
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_transactions');
    }
};
