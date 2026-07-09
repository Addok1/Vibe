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
        Schema::create('payments', function (Blueprint $table) {
            $table->string('id',16)->primary();
            $table->unsignedInteger('user_id');
            $table->double('amount', 10, 2)->default(0);
            $table->string('payment_for',30);
            $table->string('currency',5);
            $table->string('status');
            $table->boolean('is_paid');
            $table->unsignedInteger('plan_id')->nullable();
            $table->uuid('request_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');


            $table->foreign('request_id')
                    ->references('id')
                    ->on('requests')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
