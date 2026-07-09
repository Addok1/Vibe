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

        if(!Schema::hasTable('agent_commission')){
            Schema::create('agent_commission', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('service_location_id');
                $table->string('transport_type')->nullable();
                $table->tinyInteger('agent_commision_type')->nullable();
                $table->double('agent_commision',10,2)->default(0);
                $table->timestamps();
                
                $table->foreign('service_location_id')
                        ->references('id')
                        ->on('service_locations')
                        ->onDelete('cascade');
        
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_commission');
    }
};
