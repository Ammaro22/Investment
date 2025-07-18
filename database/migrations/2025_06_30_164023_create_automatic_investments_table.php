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
        Schema::create('automatic_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date')->nullable();
            $table->date('next_investment_date')->nullable();
            $table->string('investment_mode')->nullable();
            $table->float('investment_amount')->nullable();
            $table->float('expected_profit_min')->nullable();
            $table->float('expected_profit_max')->nullable();
            $table->integer('min_chance_invested')->nullable();
            $table->integer('max_chance_invested')->nullable();
            $table->boolean('active')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_investments');
    }
};
