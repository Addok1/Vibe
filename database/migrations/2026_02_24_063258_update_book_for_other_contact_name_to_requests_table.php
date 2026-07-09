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
            if (!Schema::hasColumn('requests', 'book_for_other_contact_name')) {
                Schema::table('requests', function (Blueprint $table) {
                    $table->string('book_for_other_contact_name')->after('book_for_other_contact')->nullable();
                    
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
