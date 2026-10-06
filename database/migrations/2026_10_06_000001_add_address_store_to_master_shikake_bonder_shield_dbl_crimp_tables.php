<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Address store (lokasi simpan di store) dicetak di tiket e-kanban keempat proses
 * shikake. Joint sudah punya kolomnya sejak awal; Bonder, Shield, dan Dbl Crimp
 * menyusul dengan posisi yang sama (tepat setelah address).
 */
return new class extends Migration
{
    private const TABLES = [
        'master_shikake_bonder',
        'master_shikake_shield',
        'master_shikake_dbl_crimp',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (Schema::hasColumn($tableName, 'address_store')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('address_store')->nullable()->after('address');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!Schema::hasColumn($tableName, 'address_store')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('address_store');
            });
        }
    }
};
