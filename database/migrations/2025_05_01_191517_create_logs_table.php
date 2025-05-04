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
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->longText('message');
            $table->json('context')->nullable();
            $table->string('level')->index();
            $table->string('channel')->nullable();
            $table->timestamp('record_datetime')->useCurrent();
            $table->ipAddress('remote_addr')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
