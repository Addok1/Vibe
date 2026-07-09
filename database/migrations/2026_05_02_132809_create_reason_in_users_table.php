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
            if(!Schema::hasColumn('users','reason')){
                Schema::table('users', function (Blueprint $table) {
                    $table->string('reason')->nullable()->after('active');
                });
            }
        }
    }




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reason_in_users');
    }
};
