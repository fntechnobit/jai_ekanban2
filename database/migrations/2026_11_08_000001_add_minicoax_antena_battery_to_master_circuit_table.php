<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kanban cutting Minicoax, Antena Feeder dan Battery Cable.
 *
 * - type: tambah CUTTING_MINICOAX, CUTTING_ANTENA_FEEDER, CUTTING_BATTERY_CABLE
 * - Minicoax : ACC3/ACC4 per sisi (acc_1b, acc_1c, acc_2b, acc_2c) dan barcode proses
 *              sisi B (barcode_process_2); sisi A memakai barcode_process yang sudah ada.
 * - Antena   : part_code (KODE), part_no (PART NO.), no_assy (NO ASSY).
 * - Battery  : qty_dc (QTY DC) dan tujuan TO 4-9 (t04-t09). QR mesin & QR drawing memakai
 *              qrcode_mesin / qrcode_drawing yang sudah ada. T04-T06 sudah ada di template
 *              Excel (AX-AZ) tetapi selama ini dilewati karena belum ada kolomnya.
 *
 * Kolom baru bertipe TEXT, bukan VARCHAR(255): master_circuit sudah berisi 54 kolom
 * VARCHAR(255) utf8mb4 (~1 KB per kolom, total ~56 KB) dan batas ukuran baris MySQL
 * 65.535 byte. 15 kolom VARCHAR(255) butuh ~15 KB ("Row size too large"), sedangkan
 * TEXT hanya terhitung ~10 byte per kolom - sisa ruang tetap ada untuk kolom berikutnya.
 */
return new class extends Migration
{
    /** [kolom baru => kolom sebelumnya] supaya urutan kolom tetap mudah dibaca */
    private const COLUMNS = [
        'qty_dc' => 'qty',
        'barcode_process_2' => 'barcode_process',
        'part_code' => 'shikake_code',
        'part_no' => 'part_code',
        'no_assy' => 'part_no',
        'acc_1b' => 'acc_1a',
        'acc_1c' => 'acc_1b',
        'acc_2b' => 'acc_2a',
        'acc_2c' => 'acc_2b',
        't04' => 't03',
        't05' => 't04',
        't06' => 't05',
        't07' => 't06',
        't08' => 't07',
        't09' => 't08',
    ];

    private const NEW_TYPES = ['CUTTING_MINICOAX', 'CUTTING_ANTENA_FEEDER', 'CUTTING_BATTERY_CABLE'];

    public function up(): void
    {
        DB::statement("ALTER TABLE master_circuit MODIFY type ENUM('CUTTING','CUTTING_TWIST','CUTTING_EJ','CUTTING_JAM','CUTTING_MINICOAX','CUTTING_ANTENA_FEEDER','CUTTING_BATTERY_CABLE') NOT NULL DEFAULT 'CUTTING' COMMENT 'Type of circuit process: CUTTING, CUTTING_TWIST, CUTTING_EJ, CUTTING_JAM, CUTTING_MINICOAX, CUTTING_ANTENA_FEEDER or CUTTING_BATTERY_CABLE'");

        Schema::table('master_circuit', function (Blueprint $table) {
            foreach (self::COLUMNS as $column => $after) {
                if (!Schema::hasColumn('master_circuit', $column)) {
                    $table->text($column)->nullable()->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            foreach (array_keys(self::COLUMNS) as $column) {
                if (Schema::hasColumn('master_circuit', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Baris type baru dikembalikan ke CUTTING agar enum lama bisa dipasang kembali.
        DB::table('master_circuit')->whereIn('type', self::NEW_TYPES)->update(['type' => 'CUTTING']);
        DB::statement("ALTER TABLE master_circuit MODIFY type ENUM('CUTTING','CUTTING_TWIST','CUTTING_EJ','CUTTING_JAM') NOT NULL DEFAULT 'CUTTING' COMMENT 'Type of circuit process: CUTTING, CUTTING_TWIST, CUTTING_EJ or CUTTING_JAM'");
    }
};
