<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // kasir
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // pelanggan terhubung
            $table->string('order_number')->unique();
            $table->integer('subtotal');
            $table->integer('discount')->default(0);
            $table->integer('total');
            $table->string('payment_method')->default('cash'); // cash | qris | transfer
            $table->string('status')->default('paid'); // paid | voided
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
