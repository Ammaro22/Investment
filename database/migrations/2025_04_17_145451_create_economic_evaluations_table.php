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
        Schema::create('economic_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_for_sale_id')->constrained('property_for_sales')->onDelete('cascade');
            $table->integer('number_of_chances');
            $table->string('negotiation_mode');
            $table->float('profit_percent');
            $table->float('expected_price');
            $table->float('buying_price')->nullable();
            $table->float('renting_price')->nullable();
            $table->float('total_expected_taxes');
            $table->float('chance_price');
            $table->date('investment_time');
            $table->date('incoming_time');
            $table->enum('investment_mode',['CapitalGrowth','HighIncoming','Balanced']);
            $table->enum('property_management',['selling','rent']);
            $table->foreignId('agreed_negotiation_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('economic_evaluations');
    }
};
