<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('finance_granted_by_admin')->default(false)->after('pos_admin_expires_at');
            $table->timestamp('finance_admin_expires_at')->nullable()->after('finance_granted_by_admin');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['finance_granted_by_admin', 'finance_admin_expires_at']);
        });
    }
};
