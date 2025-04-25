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
        Schema::create('agreed_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_for_sale_id')->constrained('property_for_sales')->cascadeOnDelete();
            $table->text('Text_of_the_agreement');
            $table->string('Payment_Mechanism');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreed_negotiations');
    }
};
