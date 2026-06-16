<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->unsignedInteger('card_size')->default(10)->after('name');
        });

        // Backfill dari stamps_per_reward yang lama.
        DB::table('loyalty_programs')->update(['card_size' => DB::raw('stamps_per_reward')]);
    }

    public function down(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn('card_size');
        });
    }
};
