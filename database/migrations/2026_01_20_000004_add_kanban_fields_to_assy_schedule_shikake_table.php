<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds kanban generation fields to assy_schedule_shikake table
     */
    public function up(): void
    {
        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            // Kanban generation fields
            $table->string('issue', 10)->default('001/001')->after('master_shikake_id');  // Format XXX/YYY
            $table->string('nomor_urut', 10)->default('0001')->after('issue');            // 4 digit sequence
            $table->string('barcode_kanban', 100)->nullable()->after('nomor_urut');       // Generated barcode
            $table->date('release_date')->nullable()->after('barcode_kanban');            // Tanggal release/generate
            $table->integer('qty_listing')->default(0)->after('release_date');            // Kebutuhan qty dari schedule
            $table->integer('qty_kanban')->default(0)->after('qty_listing');              // Kapasitas per kanban
            $table->integer('cutoff')->default(1)->after('qty_kanban');                   // Cutoff number
            $table->string('process', 20)->nullable()->after('cutoff');                   // TWIST/BONDER/JOINT/SHIELD/DBL_CRIMP
            
            // Indexes
            $table->index('barcode_kanban', 'idx_shikake_barcode_kanban');
            $table->index('release_date', 'idx_shikake_release_date');
            $table->index('cutoff', 'idx_shikake_cutoff');
            $table->index('process', 'idx_shikake_process');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_shikake_barcode_kanban');
            $table->dropIndex('idx_shikake_release_date');
            $table->dropIndex('idx_shikake_cutoff');
            $table->dropIndex('idx_shikake_process');
            
            // Drop columns
            $table->dropColumn([
                'issue',
                'nomor_urut',
                'barcode_kanban',
                'release_date',
                'qty_listing',
                'qty_kanban',
                'cutoff',
                'process'
            ]);
        });
    }
};
