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
        Schema::table('banner_images', function (Blueprint $table) {
            if (!Schema::hasColumn('banner_images', 'image_url')) {
                Schema::table('banner_images', function (Blueprint $table) {
                    $table->text('image_url')->after('image')->nullable();
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_images', function (Blueprint $table) {
            //
        });
    }
};
