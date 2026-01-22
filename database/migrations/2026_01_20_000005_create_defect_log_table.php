<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates defect_log table for tracking balance reductions due to defects
     */
    public function up(): void
    {
        Schema::create('defect_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->enum('type', ['circuit', 'shikake']);
            
            // Untuk Circuit
            $table->string('cct_no', 50)->nullable();
            $table->string('cct_code', 50)->nullable();
            
            // Untuk Shikake
            $table->unsignedBigInteger('master_shikake_id')->nullable();
            $table->string('shikake_type', 20)->nullable();  // BONDER/DBL_CRIMP/JOINT/SHIELD/TWIST
            
            // Defect Data
            $table->date('defect_date');                     // Tanggal defect
            $table->integer('shift');                        // Shift saat defect
            $table->integer('qty_defect');                   // Jumlah yang dikurangi
            $table->integer('balance_before');               // Balance sebelum dikurangi
            $table->integer('balance_after');                // Balance setelah dikurangi
            $table->text('reason')->nullable();              // Alasan defect
            
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defect_log');
    }
};
