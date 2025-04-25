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
        Schema::create('request_from_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_from_expert_id')->constrained('request_from_experts')->cascadeOnDelete();
            $table->foreignId('property_for_sale_id')->constrained('property_for_sales')->cascadeOnDelete();
            $table->string('status');
            $table->string('type_request');
            $table->string('front_image')->nullable();
            $table->string('back_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_from_admins');
    }
};
