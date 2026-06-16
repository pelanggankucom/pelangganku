<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FK kept as plain indexed columns for SQLite ALTER compatibility.
            $table->unsignedBigInteger('merchant_id')->nullable()->after('id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->after('merchant_id')->index();
            $table->string('role')->default('cashier')->after('password'); // owner | cashier
            $table->string('pin_hash')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['merchant_id', 'branch_id', 'role', 'pin_hash', 'is_active']);
        });
    }
};
