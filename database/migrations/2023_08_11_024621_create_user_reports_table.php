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
        Schema::create('food_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('calories');
            $table->decimal('protein', 5);
            $table->decimal('fat', 5);
            $table->decimal('carbohydrate', 5);
            $table->string('unit', 3);
            $table->date('date');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('food_entries');
    }
};
