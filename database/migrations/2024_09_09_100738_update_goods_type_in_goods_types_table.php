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
        if(Schema::hasTable('goods_types')){
            if(Schema::hasColumn('goods_types','goods_types_for')){
                Schema::table('goods_types', function (Blueprint $table) {
                    $table->string('goods_types_for')->nullable()->change();
                });
            }else{
                Schema::table('goods_types', function (Blueprint $table) {
                    $table->string('goods_types_for')->after('goods_type_name')->nullable();
                });
            }
        }
        if(Schema::hasTable('vehicle_types')){
            if(Schema::hasColumn('vehicle_types','trip_dispatch_type')){
                Schema::table('vehicle_types', function (Blueprint $table) {
                    $table->string('trip_dispatch_type')->nullable()->default('both')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasTable('goods_types')){
            if(Schema::hasColumn('goods_types','goods_types_for')){
                Schema::table('goods_types', function (Blueprint $table) {
                    $table->string('goods_types_for')->after('goods_type_name')->nullable();
                });
            }
        }
        if(Schema::hasTable('vehicle_types')){
            if(Schema::hasColumn('vehicle_types','trip_dispatch_type')){
                Schema::table('vehicle_types', function (Blueprint $table) {
                    $table->string('trip_dispatch_type')->after('goods_type_name')->nullable();
                });
            }
        }
    }
};
