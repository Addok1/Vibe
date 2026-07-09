<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransportTypeToPromoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         if (Schema::hasTable('promo')) {
            if (!Schema::hasColumn('promo', 'transport_type')) {
                Schema::table('promo', function (Blueprint $table) {
               $table->string('transport_type')->after('id')->nullable();

                });
            }

        }



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promo', function (Blueprint $table) {
            //
        });
    }
}
