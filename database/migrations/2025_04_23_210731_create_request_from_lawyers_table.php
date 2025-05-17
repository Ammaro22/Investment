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
        Schema::create('request_from_lawyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_for_sale_id')->constrained('property_for_sales')->cascadeOnDelete();
            $table->string('status');
            $table->string('accept_admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_from_lawyers');
    }
};
