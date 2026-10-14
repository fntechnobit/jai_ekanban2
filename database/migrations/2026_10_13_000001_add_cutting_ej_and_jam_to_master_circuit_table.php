<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kanban cutting EJ dan JAM Twist.
 *
 * - type: tambah CUTTING_EJ dan CUTTING_JAM
 * - EJ : qrcode_ej_a / qrcode_ej_b, isi QR per sisi (diisi dari Excel)
 * - JAM: 1 baris master = 1 pasang circuit. Kolom circuit 1 memakai kolom yang
 *        sudah ada (cct_code, cct_no, ...); circuit 2 disimpan di kolom *_2.
 *        Twist No = shikake_code, jadi tidak perlu kolom baru.
 *        qrcode_mesin = QR barcode mesin dari MTC (statis); barcode_mesin / barcode_mesin_2
 *        = barcode mesin cadangan (Code 39) per circuit.
 */
return new class extends Migration
{
    private const PAIR_COLUMNS = [
        'cct_code_2', 'cct_no_2', 'cust_no_2', 'kind_2', 'size_2', 'col_2', 'cl_2', 'barcode_mesin_2',
    ];

    public function up(): void
    {
        DB::statement("ALTER TABLE master_circuit MODIFY type ENUM('CUTTING','CUTTING_TWIST','CUTTING_EJ','CUTTING_JAM') NOT NULL DEFAULT 'CUTTING' COMMENT 'Type of circuit process: CUTTING, CUTTING_TWIST, CUTTING_EJ or CUTTING_JAM'");

        Schema::table('master_circuit', function (Blueprint $table) {
            if (!Schema::hasColumn('master_circuit', 'qrcode_ej_a')) {
                $table->string('qrcode_ej_a')->nullable()->after('qrcode_drawing');
            }
            if (!Schema::hasColumn('master_circuit', 'qrcode_ej_b')) {
                $table->string('qrcode_ej_b')->nullable()->after('qrcode_ej_a');
            }
            if (!Schema::hasColumn('master_circuit', 'qrcode_mesin')) {
                $table->string('qrcode_mesin')->nullable()->after('qrcode_ej_b');
            }

            $after = 'cl';
            foreach (self::PAIR_COLUMNS as $column) {
                if (!Schema::hasColumn('master_circuit', $column)) {
                    $table->string($column)->nullable()->after($after);
                }
                $after = $column;
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            foreach (array_merge(['qrcode_ej_a', 'qrcode_ej_b', 'qrcode_mesin'], self::PAIR_COLUMNS) as $column) {
                if (Schema::hasColumn('master_circuit', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Baris EJ/JAM dikembalikan ke CUTTING agar enum lama bisa dipasang kembali.
        DB::table('master_circuit')->whereIn('type', ['CUTTING_EJ', 'CUTTING_JAM'])->update(['type' => 'CUTTING']);
        DB::statement("ALTER TABLE master_circuit MODIFY type ENUM('CUTTING','CUTTING_TWIST') NOT NULL DEFAULT 'CUTTING' COMMENT 'Type of circuit process: CUTTING or CUTTING_TWIST'");
    }
};
