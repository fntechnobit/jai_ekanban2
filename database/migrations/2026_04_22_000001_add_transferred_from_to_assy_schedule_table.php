<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->date('transferred_from_date')->nullable()->after('cutoff');
            $table->integer('transferred_from_shift')->nullable()->after('transferred_from_date');
            $table->integer('transferred_from_cutoff')->nullable()->after('transferred_from_shift');
            $table->unsignedBigInteger('transferred_from_listing_id')->nullable()->after('transferred_from_cutoff');

            $table->index(['transferred_from_date', 'transferred_from_shift'], 'idx_assy_sched_transferred_from');
        });
    }

    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropIndex('idx_assy_sched_transferred_from');
            $table->dropColumn([
                'transferred_from_date',
                'transferred_from_shift',
                'transferred_from_cutoff',
                'transferred_from_listing_id',
            ]);
        });
    }
};
