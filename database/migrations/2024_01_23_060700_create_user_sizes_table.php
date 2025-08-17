<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedDecimal('neck')->nullable();
            $table->unsignedDecimal('chest')->nullable();
            $table->unsignedDecimal('waist')->nullable();
            $table->unsignedDecimal('biceps')->nullable();
            $table->unsignedDecimal('pelvis')->nullable();
            $table->unsignedDecimal('thigh')->nullable();
            $table->unsignedDecimal('tibia')->nullable();
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_sizes');
    }
};
