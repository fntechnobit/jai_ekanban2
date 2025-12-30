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
        Schema::create('assy_schedule_circuit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assy_schedule_id');
            $table->unsignedBigInteger('circuit_id');
            $table->boolean('is_printed')->default(false);
            $table->timestamp('last_printed_at')->nullable();
            $table->unsignedBigInteger('last_printed_by')->nullable();
            $table->integer('print_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->unique(['assy_schedule_id', 'circuit_id'], 'unique_assy_circuit');
            $table->index('is_printed', 'idx_is_printed');
            $table->index('assy_schedule_id', 'idx_assy_schedule');
            $table->index('circuit_id', 'idx_circuit');
            
            // Foreign keys
            $table->foreign('assy_schedule_id')
                  ->references('id')->on('assy_schedule')
                  ->onDelete('cascade');
            $table->foreign('circuit_id')
                  ->references('id')->on('master_circuit')
                  ->onDelete('cascade');
            $table->foreign('last_printed_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assy_schedule_circuit');
    }
};
