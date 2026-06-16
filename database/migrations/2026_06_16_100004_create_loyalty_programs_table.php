<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Program Stempel');
            $table->unsignedInteger('stamps_per_reward')->default(10);
            $table->string('earn_rule')->default('per_visit'); // per_visit | per_amount
            $table->unsignedInteger('amount_per_stamp')->nullable(); // rupiah per 1 stempel (jika per_amount)
            $table->boolean('carry_over')->default(true); // sisa stempel dibawa setelah redeem
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
