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
        Schema::create('property_for_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('property_type');
            $table->decimal('area', 8, 2);
            $table->float('number_of_rooms');
            $table->float('number_of_bathrooms');
            $table->float('property_age');
            $table->string('decoration');
            $table->string('kitchen_type');
            $table->string('flooring_type');
            $table->float('overlook_from');
            $table->decimal('balcony_size', 5, 2);
            $table->string('painting_type');
            $table->decimal('price', 10, 2);
            $table->string('pay_way');
            $table->string('state');
            $table->string('exact_position');
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
        Schema::dropIfExists('real_estates');
    }
};
