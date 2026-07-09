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
       
        if (Schema::hasTable('bannersimages')) {
            if (!Schema::hasColumn('bannersimages', 'banner_bg_color')) {
                Schema::table('bannersimages', function (Blueprint $table) {
                    $table->string('banner_bg_color')->after('enable_banner_button')->nullable();
                    $table->string('banner_title_color')->after('banner_bg_color')->nullable();
                    $table->string('banner_description_color')->after('banner_title_color')->nullable();
                    $table->string('banner_button_color')->after('banner_description_color')->nullable();
                    $table->string('banner_button_text_color')->after('banner_button_color')->nullable();
                    
                });
            }

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bannerimages', function (Blueprint $table) {
            //
        });
    }
};
