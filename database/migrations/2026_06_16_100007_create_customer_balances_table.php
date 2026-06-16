<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stamps_current')->default(0);
            $table->unsignedInteger('lifetime_stamps')->default(0);
            $table->timestamps();

            $table->unique(['customer_id', 'loyalty_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balances');
    }
};
