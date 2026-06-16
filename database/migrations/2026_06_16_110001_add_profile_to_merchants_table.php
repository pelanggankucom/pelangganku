<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('phone')->nullable()->after('address');
            $table->string('logo_path')->nullable()->after('phone');
            $table->string('photo_path')->nullable()->after('logo_path');
            $table->string('instagram')->nullable()->after('photo_path');
            $table->string('whatsapp')->nullable()->after('instagram');
            $table->string('facebook')->nullable()->after('whatsapp');
            $table->string('tiktok')->nullable()->after('facebook');
            $table->string('website')->nullable()->after('tiktok');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'phone', 'logo_path', 'photo_path',
                'instagram', 'whatsapp', 'facebook', 'tiktok', 'website',
            ]);
        });
    }
};
