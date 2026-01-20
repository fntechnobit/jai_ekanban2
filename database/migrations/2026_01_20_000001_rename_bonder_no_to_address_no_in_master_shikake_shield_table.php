<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renames bonder_no_1 to address_no_1_1 and bonder_no_2 to address_no_1_2
     * in master_shikake_shield table. This preserves existing data.
     */
    public function up(): void
    {
        Schema::table('master_shikake_shield', function (Blueprint $table) {
            $table->renameColumn('bonder_no_1', 'address_no_1_1');
            $table->renameColumn('bonder_no_2', 'address_no_1_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_shikake_shield', function (Blueprint $table) {
            $table->renameColumn('address_no_1_1', 'bonder_no_1');
            $table->renameColumn('address_no_1_2', 'bonder_no_2');
        });
    }
};
