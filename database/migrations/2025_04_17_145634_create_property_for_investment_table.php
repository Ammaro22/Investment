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
        Schema::create('property_for_investment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('property_for_sales')->onDelete('cascade');
            $table->integer('total_chance');
            $table->decimal('expected_price',12,2);
            $table->decimal('return_rate',5,2);
            $table->decimal('progress_percent',5,2)->default(0);
            $table->decimal('chance_price', 10, 2);
            $table->date('deadline_investment');
            $table->enum('investment_type',['capital_growth','high_incoming','balanced']);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_for_investment');
    }
};
