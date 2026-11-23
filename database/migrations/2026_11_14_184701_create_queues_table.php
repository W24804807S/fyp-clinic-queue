<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to create the PCPD-compliant anonymous queues table.
     */
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            
            // Anonymous queue token (e.g., A-001) - No PII collected per HK PCPD guidelines
            $table->string('token_number')->unique();
            
            // Queue status: 'waiting', 'serving', 'completed', 'cancelled'
            $table->string('status')->default('waiting');
            
            // Priority flag for urgent walk-ins or elderly patients (higher number = higher priority)
            $table->integer('priority')->default(0);
            
            // Timestamp when patient joins the queue (used to calculate arrival rate λ)
            $table->timestamp('arrived_at')->nullable();
            
            // Timestamp when consultation begins (used to calculate waiting time & service rate μ)
            $table->timestamp('served_at')->nullable();
            
            // Timestamp when consultation finishes
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};