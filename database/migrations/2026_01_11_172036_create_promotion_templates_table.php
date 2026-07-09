<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if(!Schema::hasTable('promotion_templates')){
        Schema::create('promotion_templates', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('html');
            $table->string('preview_image')->nullable();
            $table->dateTime('from')->nullable();
            $table->dateTime('to')->nullable();
            $table->string('time')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    }

    public function down() {
        Schema::dropIfExists('promotion_templates');
    }
};
