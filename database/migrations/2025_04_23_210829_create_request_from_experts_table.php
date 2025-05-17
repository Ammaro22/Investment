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
        Schema::create('request_from_experts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_from_lawyer_id')->constrained('request_from_lawyers')->cascadeOnDelete();
            $table->foreignId('economic_evaluation_id')->constrained('economic_evaluations')->cascadeOnDelete();
            $table->string('status');
            $table->text('note_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_from_experts');
    }
};
