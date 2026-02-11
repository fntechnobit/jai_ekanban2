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
        Schema::table('master_circuit', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('barcode', 'barcode_kanban');
            $table->renameColumn('c_l', 'cl');
        });
        
        Schema::table('master_circuit', function (Blueprint $table) {
            // Add machine_sequence column after issue
            $table->string('machine_sequence')->nullable()->after('issue');
            
            // Drop old machine and sequence columns
            $table->dropColumn(['machine', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            // Restore machine and sequence columns
            $table->string('machine')->nullable()->after('issue');
            $table->integer('sequence')->nullable()->after('machine');
            
            // Drop machine_sequence column
            $table->dropColumn('machine_sequence');
        });
        
        Schema::table('master_circuit', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('barcode_kanban', 'barcode');
            $table->renameColumn('cl', 'c_l');
        });
    }
};
