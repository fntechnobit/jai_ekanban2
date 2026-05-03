<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create addition_log_circuit and addition_log_shikake tables.
     */
    public function up(): void
    {
        // 1. Create addition_log_circuit table
        Schema::create('addition_log_circuit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_circuit_id');

            // Addition Data
            $table->date('addition_date');
            $table->integer('shift');
            $table->integer('qty_addition');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('addition_date', 'idx_circuit_addition_date');
            $table->index('conveyor_id', 'idx_circuit_addition_conveyor');
            $table->index('master_circuit_id', 'idx_circuit_addition_circuit');

            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_circuit_id')
                  ->references('id')->on('master_circuit')
                  ->onDelete('cascade');
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });

        // 2. Create addition_log_shikake table
        Schema::create('addition_log_shikake', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_shikake_id');
            $table->string('shikake_type', 20)->nullable(); // BONDER/DBL_CRIMP/JOINT/SHIELD/TWIST

            // Addition Data
            $table->date('addition_date');
            $table->integer('shift');
            $table->integer('qty_addition');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('addition_date', 'idx_shikake_addition_date');
            $table->index('conveyor_id', 'idx_shikake_addition_conveyor');
            $table->index('master_shikake_id', 'idx_shikake_addition_shikake');
            $table->index('shikake_type', 'idx_shikake_addition_type');

            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_shikake_id')
                  ->references('id')->on('master_shikake')
                  ->onDelete('cascade');
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addition_log_shikake');
        Schema::dropIfExists('addition_log_circuit');
    }
};
