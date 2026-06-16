<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            // Satu hadiah hanya bisa diklaim sekali per kartu berjalan.
            $table->unique(['customer_id', 'reward_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_claims');
    }
};
