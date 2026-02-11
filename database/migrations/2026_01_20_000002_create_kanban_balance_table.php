<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates kanban_balance table for tracking carry-over balance between shifts
     */
    public function up(): void
    {
        Schema::create('kanban_balance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->enum('type', ['circuit', 'shikake']);
            
            // Untuk Circuit
            $table->string('cct_no', 50)->nullable();
            $table->string('cct_code', 50)->nullable();
            
            // Untuk Shikake
            $table->unsignedBigInteger('master_shikake_id')->nullable();
            
            // Balance tracking
            $table->integer('sisa')->default(0);                 // Sisa kanban dari periode terakhir
            $table->integer('last_nomor_urut')->default(0);      // Nomor urut terakhir
            $table->unsignedBigInteger('last_schedule_id')->nullable(); // Schedule terakhir yang di-generate
            $table->date('last_schedule_date')->nullable();      // Tanggal schedule terakhir
            $table->integer('last_shift')->nullable();           // Shift terakhir
            
            $table->timestamps();
            
            // Indexes
            $table->index(['conveyor_id', 'type'], 'idx_conveyor_type');
            $table->index(['cct_no', 'cct_code'], 'idx_circuit_codes');
            $table->index('master_shikake_id', 'idx_shikake');
            
            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_shikake_id')
                  ->references('id')->on('master_shikake')
                  ->onDelete('set null');
            $table->foreign('last_schedule_id')
                  ->references('id')->on('assy_schedule')
                  ->onDelete('set null');
        });
        
        // Add unique constraints (partial) - MySQL doesn't support partial unique indexes,
        // so we'll handle uniqueness in the application layer or use composite unique
        // For circuit: unique on (conveyor_id, type, cct_no, cct_code) where type='circuit'
        // For shikake: unique on (conveyor_id, type, master_shikake_id) where type='shikake'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kanban_balance');
    }
};
