<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Separates kanban_balance into kanban_balance_circuit and kanban_balance_shikake
     */
    public function up(): void
    {
        // 1. Create kanban_balance_circuit table
        Schema::create('kanban_balance_circuit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_circuit_id');
            
            // Balance tracking
            $table->integer('sisa')->default(0);
            $table->integer('last_nomor_urut')->default(0);
            $table->unsignedBigInteger('last_schedule_id')->nullable();
            $table->date('last_schedule_date')->nullable();
            $table->integer('last_shift')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['conveyor_id', 'master_circuit_id'], 'unique_circuit_balance');
            
            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_circuit_id')
                  ->references('id')->on('master_circuit')
                  ->onDelete('cascade');
            $table->foreign('last_schedule_id')
                  ->references('id')->on('assy_schedule')
                  ->onDelete('set null');
        });
        
        // 2. Create kanban_balance_shikake table
        Schema::create('kanban_balance_shikake', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_shikake_id');
            
            // Balance tracking
            $table->integer('sisa')->default(0);
            $table->integer('last_nomor_urut')->default(0);
            $table->unsignedBigInteger('last_schedule_id')->nullable();
            $table->date('last_schedule_date')->nullable();
            $table->integer('last_shift')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['conveyor_id', 'master_shikake_id'], 'unique_shikake_balance');
            
            // Foreign keys
            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
            $table->foreign('master_shikake_id')
                  ->references('id')->on('master_shikake')
                  ->onDelete('cascade');
            $table->foreign('last_schedule_id')
                  ->references('id')->on('assy_schedule')
                  ->onDelete('set null');
        });
        
        // 3. Migrate existing circuit data (lookup master_circuit_id by cct_no + cct_code)
        DB::statement("
            INSERT INTO kanban_balance_circuit (conveyor_id, master_circuit_id, sisa, last_nomor_urut, last_schedule_id, last_schedule_date, last_shift, created_at, updated_at)
            SELECT 
                kb.conveyor_id,
                mc.id as master_circuit_id,
                kb.sisa,
                kb.last_nomor_urut,
                kb.last_schedule_id,
                kb.last_schedule_date,
                kb.last_shift,
                kb.created_at,
                kb.updated_at
            FROM kanban_balance kb
            INNER JOIN master_circuit mc ON mc.cct_no = kb.cct_no AND mc.cct_code = kb.cct_code
            WHERE kb.type = 'circuit'
        ");
        
        // 4. Migrate existing shikake data
        DB::statement("
            INSERT INTO kanban_balance_shikake (conveyor_id, master_shikake_id, sisa, last_nomor_urut, last_schedule_id, last_schedule_date, last_shift, created_at, updated_at)
            SELECT 
                conveyor_id,
                master_shikake_id,
                sisa,
                last_nomor_urut,
                last_schedule_id,
                last_schedule_date,
                last_shift,
                created_at,
                updated_at
            FROM kanban_balance
            WHERE type = 'shikake' AND master_shikake_id IS NOT NULL
        ");
        
        // 5. Drop old kanban_balance table
        Schema::dropIfExists('kanban_balance');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate original kanban_balance table
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
            $table->integer('sisa')->default(0);
            $table->integer('last_nomor_urut')->default(0);
            $table->unsignedBigInteger('last_schedule_id')->nullable();
            $table->date('last_schedule_date')->nullable();
            $table->integer('last_shift')->nullable();
            
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
        
        // 2. Migrate data back from circuit table
        DB::statement("
            INSERT INTO kanban_balance (conveyor_id, type, cct_no, cct_code, sisa, last_nomor_urut, last_schedule_id, last_schedule_date, last_shift, created_at, updated_at)
            SELECT 
                kbc.conveyor_id,
                'circuit' as type,
                mc.cct_no,
                mc.cct_code,
                kbc.sisa,
                kbc.last_nomor_urut,
                kbc.last_schedule_id,
                kbc.last_schedule_date,
                kbc.last_shift,
                kbc.created_at,
                kbc.updated_at
            FROM kanban_balance_circuit kbc
            INNER JOIN master_circuit mc ON mc.id = kbc.master_circuit_id
        ");
        
        // 3. Migrate data back from shikake table
        DB::statement("
            INSERT INTO kanban_balance (conveyor_id, type, master_shikake_id, sisa, last_nomor_urut, last_schedule_id, last_schedule_date, last_shift, created_at, updated_at)
            SELECT 
                conveyor_id,
                'shikake' as type,
                master_shikake_id,
                sisa,
                last_nomor_urut,
                last_schedule_id,
                last_schedule_date,
                last_shift,
                created_at,
                updated_at
            FROM kanban_balance_shikake
        ");
        
        // 4. Drop new tables
        Schema::dropIfExists('kanban_balance_circuit');
        Schema::dropIfExists('kanban_balance_shikake');
    }
};
