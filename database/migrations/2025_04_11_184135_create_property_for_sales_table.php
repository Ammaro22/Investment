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
    public function up(){    Schema::create('property_for_sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('property_type');
        $table->decimal('area', 8, 2);
        $table->float('number_of_rooms')->nullable();
        $table->float('number_of_bathrooms')->nullable();
        $table->float('property_age');
        $table->string('decoration')->nullable();
        $table->string('kitchen_type')->nullable();
        $table->string('flooring_type')->nullable();
        $table->float('overlook_from')->nullable();
        $table->decimal('balcony_size', 5, 2)->nullable();
        $table->string('painting_type')->nullable();
        $table->decimal('price', 15, 2);
        $table->string('pay_way');
        $table->string('state');
        $table->string('exact_position');
        $table->enum('contract',['buying','investment']);
        $table->boolean('legal_check');
        $table->boolean('expert_check');
        $table->boolean('accept');
        $table->string('status');
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
