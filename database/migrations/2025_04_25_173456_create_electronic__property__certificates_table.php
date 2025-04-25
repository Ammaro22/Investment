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
        Schema::create('electronic__property__certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_from_admin_id')->constrained('request_from_admins')->cascadeOnDelete();
            $table->string('Seller_Name');
            $table->string('Property_Location');
            $table->string('lawyer_Name');
            $table->string('Property_Price');
            $table->string('Payment_Method')->default('cash');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronic__property__certificates');
    }
};
