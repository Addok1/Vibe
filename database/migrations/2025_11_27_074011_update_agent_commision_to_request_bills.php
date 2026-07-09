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
         if (Schema::hasTable('request_bills')) {
            if (!Schema::hasColumn('request_bills', 'agent_commision')) {
                Schema::table('request_bills', function (Blueprint $table) {
                    $table->uuid('agent_commision', 10, 2)->after('preference_price_total')->default(0);
                });
            }
        } 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_bills', function (Blueprint $table) {
            //
        });
    }
};
