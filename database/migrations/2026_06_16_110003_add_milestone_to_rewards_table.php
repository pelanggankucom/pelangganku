<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->unsignedInteger('milestone')->default(10)->after('name');
            $table->string('image_path')->nullable()->after('milestone');
            $table->text('terms')->nullable()->after('image_path');
        });

        // Backfill milestone dari cost_stamps lama.
        DB::table('rewards')->update(['milestone' => DB::raw('cost_stamps')]);
    }

    public function down(): void
    {
        Schema::table('rewards', function (Blueprint $table) {
            $table->dropColumn(['milestone', 'image_path', 'terms']);
        });
    }
};
