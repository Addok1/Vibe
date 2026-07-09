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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'stripe_customer_environment')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_customer_environment')->nullable()->after('stripe_customer_id');
            });
        }

        if (Schema::hasTable('card_info') && !Schema::hasColumn('card_info', 'stripe_environment')) {
            Schema::table('card_info', function (Blueprint $table) {
                $table->string('stripe_environment')->nullable()->after('merchant_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'stripe_customer_environment')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stripe_customer_environment');
            });
        }

        if (Schema::hasTable('card_info') && Schema::hasColumn('card_info', 'stripe_environment')) {
            Schema::table('card_info', function (Blueprint $table) {
                $table->dropColumn('stripe_environment');
            });
        }
    }
};

