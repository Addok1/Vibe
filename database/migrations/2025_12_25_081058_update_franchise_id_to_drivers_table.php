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
        if (Schema::hasTable('drivers')) {
            if (!Schema::hasColumn('drivers', 'franchise_owner_id')) {
                Schema::table('drivers', function (Blueprint $table) {
                    $table->uuid('franchise_owner_id')->after('owner_id')->nullable();

                  
                });
            }
             
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            //
        });
    }
};
