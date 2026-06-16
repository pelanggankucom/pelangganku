<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_canonical');   // 628xxxxxxxxx (E.164 tanpa +)
            $table->string('phone_raw')->nullable();
            $table->date('dob')->nullable();
            $table->unsignedBigInteger('created_branch_id')->nullable()->index();
            $table->timestamps();

            // Anti-duplikasi: 1 nomor unik per merchant.
            $table->unique(['merchant_id', 'phone_canonical']);
            // Pencarian cepat per nomor (< 1 detik).
            $table->index('phone_canonical');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
