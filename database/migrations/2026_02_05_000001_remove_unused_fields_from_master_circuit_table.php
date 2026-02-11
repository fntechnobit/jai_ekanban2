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
            $table->dropColumn([
                'remark_1',
                'remark_2',
                'ta',
                'tb',
                't04',
                't05',
                't06',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_circuit', function (Blueprint $table) {
            $table->text('remark_1')->nullable()->after('mark_1');
            $table->text('remark_2')->nullable()->after('mark_2');
            $table->string('ta')->nullable()->after('remark_2');
            $table->string('tb')->nullable()->after('ta');
            $table->string('t04')->nullable()->after('t03');
            $table->string('t05')->nullable()->after('t04');
            $table->string('t06')->nullable()->after('t05');
        });
    }
};
