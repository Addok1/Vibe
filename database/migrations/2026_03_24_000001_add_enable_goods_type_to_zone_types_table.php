<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zone_types', function (Blueprint $table) {
            if (!Schema::hasColumn('zone_types', 'enable_goods_type')) {
                $table->tinyInteger('enable_goods_type')
                    ->after('enable_shared_ride')
                    ->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('zone_types', function (Blueprint $table) {
            if (Schema::hasColumn('zone_types', 'enable_goods_type')) {
                $table->dropColumn('enable_goods_type');
            }
        });
    }
};
