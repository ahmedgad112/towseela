<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->string('governorate')->nullable()->after('name_en');
            $table->json('aliases')->nullable()->after('governorate');
            $table->index(['is_active', 'governorate']);
        });

        DB::table('delivery_zones')
            ->whereNull('governorate')
            ->update(['governorate' => 'القاهرة']);
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'governorate']);
            $table->dropColumn(['governorate', 'aliases']);
        });
    }
};
