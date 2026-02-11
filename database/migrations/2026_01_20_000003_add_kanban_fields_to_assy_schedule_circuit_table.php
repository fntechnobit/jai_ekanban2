<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds kanban generation fields to assy_schedule_circuit table
     */
    public function up(): void
    {
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            // Reference to master circuit
            $table->unsignedBigInteger('master_circuit_id')->nullable()->after('cct_code');
            
            // Kanban generation fields
            $table->string('issue', 10)->default('001/001')->after('master_circuit_id');  // Format XXX/YYY
            $table->string('nomor_urut', 10)->default('0001')->after('issue');            // 4 digit sequence
            $table->string('barcode_kanban', 100)->nullable()->after('nomor_urut');       // Generated barcode
            $table->date('release_date')->nullable()->after('barcode_kanban');            // Tanggal release/generate
            $table->integer('qty_listing')->default(0)->after('release_date');            // Kebutuhan qty dari schedule
            $table->integer('qty_kanban')->default(0)->after('qty_listing');              // Kapasitas per kanban
            $table->integer('cutoff')->default(1)->after('qty_kanban');                   // Cutoff number
            
            // Indexes
            $table->index('master_circuit_id', 'idx_master_circuit');
            $table->index('barcode_kanban', 'idx_barcode_kanban');
            $table->index('release_date', 'idx_release_date');
            $table->index('cutoff', 'idx_cutoff');
            
            // Foreign key
            $table->foreign('master_circuit_id')
                  ->references('id')->on('master_circuit')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule_circuit', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['master_circuit_id']);
            
            // Drop indexes
            $table->dropIndex('idx_master_circuit');
            $table->dropIndex('idx_barcode_kanban');
            $table->dropIndex('idx_release_date');
            $table->dropIndex('idx_cutoff');
            
            // Drop columns
            $table->dropColumn([
                'master_circuit_id',
                'issue',
                'nomor_urut',
                'barcode_kanban',
                'release_date',
                'qty_listing',
                'qty_kanban',
                'cutoff'
            ]);
        });
    }
};
