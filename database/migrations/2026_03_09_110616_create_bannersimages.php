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
        Schema::create('bannersimages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appmodule_id')->nullable(); 
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('bannertype')->nullable();
            $table->string('imageurl')->nullable();
            $table->string('image')->nullable();
            $table->text('button_name')->nullable();
            $table->boolean('enable_banner_button')->default(0);
            $table->string('previewimage')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign('appmodule_id')
                ->references('id')
                ->on('mobile_app_settings')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bannersimages');
    }
};
