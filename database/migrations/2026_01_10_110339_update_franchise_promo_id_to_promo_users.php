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
       
        if (Schema::hasTable('promo_users')) {

            if (!Schema::hasColumn('promo_users', 'franchise_promo_id')) {
                Schema::table('promo_users', function (Blueprint $table) {
                    $table->uuid('franchise_promo_id')->after('promo_code_id')->nullable();
                    $table->uuid('promo_code_id')->nullable()->change();

                });
            }
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo__users', function (Blueprint $table) {
            //
        });
    }
};
