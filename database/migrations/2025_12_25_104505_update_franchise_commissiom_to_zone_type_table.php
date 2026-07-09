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
            
            if (!Schema::hasColumn('zone_types', 'franchise_commision_type')) {
                Schema::table('zone_types', function (Blueprint $table) {
                $table->tinyInteger('franchise_commision_type')->after('agent_commision')->nullable();
                });
            }
            if (!Schema::hasColumn('zone_types', 'franchise_commision')) {
                Schema::table('zone_types', function (Blueprint $table) {
                $table->double('franchise_commision',10,2)->after('franchise_commision_type')->default(0);
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
