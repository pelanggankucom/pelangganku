<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            // Perkiraan nominal hadiah (Rupiah) untuk menghitung penghematan pelanggan.
            $table->unsignedInteger('value')->nullable()->after('terms');
        });
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
