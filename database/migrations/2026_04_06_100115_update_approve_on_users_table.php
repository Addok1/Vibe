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
        if(Schema::hasTable('users')){
            if(!Schema::hasColumn('users','approve')){
                Schema::table('users', function (Blueprint $table) {
                    $table->boolean('approve')->default(false)->after('active');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasTable('users')){
            if(Schema::hasColumn('users','approve')){
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('approve');
                });
            }
        }
    }
};
