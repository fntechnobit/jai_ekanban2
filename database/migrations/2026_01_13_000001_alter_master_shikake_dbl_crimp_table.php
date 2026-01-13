<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old index if exists
        $indexExists = DB::select("SHOW INDEX FROM master_shikake_dbl_crimp WHERE Key_name = 'idx_dbl_crimp_shield_no'");
        if (!empty($indexExists)) {
            Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
                $table->dropIndex('idx_dbl_crimp_shield_no');
            });
        }

        // Check if old columns exist before dropping
        $hasShieldNo = Schema::hasColumn('master_shikake_dbl_crimp', 'shield_no');
        $hasDblCrimp = Schema::hasColumn('master_shikake_dbl_crimp', 'dbl_crimp');

        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) use ($hasShieldNo, $hasDblCrimp) {
            // Drop old columns if they exist
            $columnsToDrop = [];
            if ($hasShieldNo) $columnsToDrop[] = 'shield_no';
            if ($hasDblCrimp) $columnsToDrop[] = 'dbl_crimp';
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Add new columns if they don't exist
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'drawing_no')) {
                $table->string('drawing_no')->nullable()->after('master_shikake_id');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address')) {
                $table->string('address')->nullable()->after('drawing_no');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'barcode_mesin')) {
                $table->string('barcode_mesin')->nullable()->after('address');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'to_machine')) {
                $table->string('to_machine')->nullable()->after('barcode_mesin');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'cct_no_1')) {
                $table->string('cct_no_1')->nullable()->after('to_machine');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address_1')) {
                $table->string('address_1')->nullable()->after('cct_no_1');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'cct_no_2')) {
                $table->string('cct_no_2')->nullable()->after('address_1');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address_2')) {
                $table->string('address_2')->nullable()->after('cct_no_2');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'cct_no_3')) {
                $table->string('cct_no_3')->nullable()->after('address_2');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address_3')) {
                $table->string('address_3')->nullable()->after('cct_no_3');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'cct_no_4')) {
                $table->string('cct_no_4')->nullable()->after('address_3');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address_4')) {
                $table->string('address_4')->nullable()->after('cct_no_4');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'cct_no_5')) {
                $table->string('cct_no_5')->nullable()->after('address_4');
            }
            if (!Schema::hasColumn('master_shikake_dbl_crimp', 'address_5')) {
                $table->string('address_5')->nullable()->after('cct_no_5');
            }
        });

        // Add new indexes if they don't exist
        $drawingNoIndex = DB::select("SHOW INDEX FROM master_shikake_dbl_crimp WHERE Key_name = 'idx_dbl_crimp_drawing_no'");
        $barcodeMesinIndex = DB::select("SHOW INDEX FROM master_shikake_dbl_crimp WHERE Key_name = 'idx_dbl_crimp_barcode_mesin'");
        
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) use ($drawingNoIndex, $barcodeMesinIndex) {
            if (empty($drawingNoIndex)) {
                $table->index('drawing_no', 'idx_dbl_crimp_drawing_no');
            }
            if (empty($barcodeMesinIndex)) {
                $table->index('barcode_mesin', 'idx_dbl_crimp_barcode_mesin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex('idx_dbl_crimp_drawing_no');
            $table->dropIndex('idx_dbl_crimp_barcode_mesin');
        });

        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'drawing_no',
                'address',
                'barcode_mesin',
                'to_machine',
                'cct_no_1',
                'address_1',
                'cct_no_2',
                'address_2',
                'cct_no_3',
                'address_3',
                'cct_no_4',
                'address_4',
                'cct_no_5',
                'address_5',
            ]);

            // Restore old columns
            $table->string('shield_no')->nullable()->after('master_shikake_id');
            $table->string('dbl_crimp')->nullable()->after('shield_no');
        });

        // Restore old index
        Schema::table('master_shikake_dbl_crimp', function (Blueprint $table) {
            $table->index('shield_no', 'idx_dbl_crimp_shield_no');
        });
    }
};
