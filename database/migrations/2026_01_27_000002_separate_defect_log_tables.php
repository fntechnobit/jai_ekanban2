<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Separates defect_log into defect_log_circuit and defect_log_shikake
     */
    public function up(): void
    {
        // 1. Create defect_log_circuit table
        Schema::create('defect_log_circuit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_circuit_id');
            
            // Defect Data
            $table->date('defect_date');
            $table->integer('shift');
            $table->integer('qty_defect');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('defect_date', 'idx_circuit_defect_date');
            $table->index('conveyor_id', 'idx_circuit_defect_conveyor');
            $table->index('master_circuit_id', 'idx_circuit_defect_circuit');
            
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
        
        // 2. Create defect_log_shikake table
        Schema::create('defect_log_shikake', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_shikake_id');
            $table->string('shikake_type', 20)->nullable();  // BONDER/DBL_CRIMP/JOINT/SHIELD/TWIST
            
            // Defect Data
            $table->date('defect_date');
            $table->integer('shift');
            $table->integer('qty_defect');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('defect_date', 'idx_shikake_defect_date');
            $table->index('conveyor_id', 'idx_shikake_defect_conveyor');
            $table->index('master_shikake_id', 'idx_shikake_defect_shikake');
            $table->index('shikake_type', 'idx_shikake_defect_type');
            
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
        
        // 3. Drop old defect_log table
        Schema::dropIfExists('defect_log');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate original defect_log table
        Schema::create('defect_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->enum('type', ['circuit', 'shikake']);
            
            // Untuk Circuit
            $table->string('cct_no', 50)->nullable();
            $table->string('cct_code', 50)->nullable();
            
            // Untuk Shikake
            $table->unsignedBigInteger('master_shikake_id')->nullable();
            $table->string('shikake_type', 20)->nullable();
            
            // Defect Data
            $table->date('defect_date');
            $table->integer('shift');
            $table->integer('qty_defect');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('reason')->nullable();
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('defect_date', 'idx_defect_date');
            $table->index('conveyor_id', 'idx_defect_conveyor');
            $table->index('type', 'idx_defect_type');
            $table->index(['cct_no', 'cct_code'], 'idx_defect_circuit');
            $table->index('master_shikake_id', 'idx_defect_shikake');
            
            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_shikake_id')
                  ->references('id')->on('master_shikake')
                  ->onDelete('set null');
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
        
        // 2. Drop new tables
        Schema::dropIfExists('defect_log_circuit');
        Schema::dropIfExists('defect_log_shikake');
    }
};
