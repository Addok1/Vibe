<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('zone_types') && !Schema::hasColumn('zone_types', 'app_module_id')) {
            Schema::table('zone_types', function (Blueprint $table) {
                $table->uuid('app_module_id')->nullable()->after('type_id');
                $table->index('app_module_id');
                $table->foreign('app_module_id')
                    ->references('id')
                    ->on('mobile_app_settings')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('zone_types') && Schema::hasColumn('zone_types', 'app_module_id')) {
            Schema::table('zone_types', function (Blueprint $table) {
                $table->dropForeign(['app_module_id']);
                $table->dropIndex(['app_module_id']);
                $table->dropColumn('app_module_id');
            });
        }
    }
};
