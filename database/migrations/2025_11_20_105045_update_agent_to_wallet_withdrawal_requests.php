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
        if (Schema::hasTable('wallet_withdrawal_requests')) {
            if (!Schema::hasColumn('wallet_withdrawal_requests', 'agent_id')) {
                Schema::table('wallet_withdrawal_requests', function (Blueprint $table) {
                    $table->uuid('agent_id')->after('driver_id')->nullable();
                });
            }
        } 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_withdrawal_requests', function (Blueprint $table) {
            //
        });
    }
};
