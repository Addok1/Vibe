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
        if (Schema::hasTable('requests')) {
            if (!Schema::hasColumn('requests', 'franchise_owner_id')) {
                Schema::table('requests', function (Blueprint $table) {
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
        Schema::table('requests', function (Blueprint $table) {
            //
        });
    }
};
