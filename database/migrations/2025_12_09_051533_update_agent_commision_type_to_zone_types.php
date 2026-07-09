<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('zone_types')) {
            
            if (!Schema::hasColumn('zone_types', 'agent_commision_type')) {
                Schema::table('zone_types', function (Blueprint $table) {
                $table->tinyInteger('agent_commision_type')->after('admin_commission_from_driver')->nullable();
                });
            }
            if (!Schema::hasColumn('zone_types', 'agent_commision')) {
                Schema::table('zone_types', function (Blueprint $table) {
                $table->double('agent_commision',10,2)->after('agent_commision_type')->default(0);
                });
            }
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zone_types', function (Blueprint $table) {
            //
        });
    }
};
