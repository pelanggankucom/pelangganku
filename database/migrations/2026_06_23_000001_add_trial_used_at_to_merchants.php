<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->timestamp('pos_trial_used_at')->nullable()->after('pos_admin_expires_at');
            $table->timestamp('finance_trial_used_at')->nullable()->after('finance_admin_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['pos_trial_used_at', 'finance_trial_used_at']);
        });
    }
};
