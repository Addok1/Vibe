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
        Schema::table('cancellation_reasons', function (Blueprint $table) {
            Schema::table('cancellation_reasons', function (Blueprint $table) {
                if (!Schema::hasColumn('cancellation_reasons', 'compensate_from')) {
                    $table->string('compensate_from')->after('translation_dataset')->nullable();
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cancellation_reasons', function (Blueprint $table) {
            //
        });
    }
};
