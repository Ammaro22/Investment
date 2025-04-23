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
            $table->foreignId('property_id')->constrained('property_for_sales')->onDelete('cascade');
            $table->integer('number_of_chances');
            $table->decimal('profit_percent',10,2);
            $table->decimal('expected_price', 12, 2);
            $table->decimal('buying_price', 12, 2);
            $table->decimal('renting_price',12,2);
            $table->decimal('total_expected_taxes', 10, 2);
            $table->decimal('chance_price', 10, 2);
            $table->date('investment_time');
            $table->date('incoming_time');
            $table->enum('investment_mode',['CapitalGrowth','HighIncoming','Balanced']);
            $table->enum('property_management',['selling','rent']);
            $table->enum('status',['pending','rejected','approved','negotiation'])->default('pending');
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
