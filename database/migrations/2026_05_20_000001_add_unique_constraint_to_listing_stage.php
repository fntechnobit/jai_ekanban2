<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows before adding unique constraint
        \DB::statement("
            DELETE t1 FROM listing_stage t1
            INNER JOIN listing_stage t2
                ON  t1.id > t2.id
                AND t1.listing_date_time = t2.listing_date_time
                AND t1.conveyor          = t2.conveyor
                AND t1.assycode         = t2.assycode
                AND t1.shift             = t2.shift
                AND t1.seq               = t2.seq
        ");

        Schema::table('listing_stage', function (Blueprint $table) {
            $table->unique(['listing_date_time', 'conveyor', 'assycode', 'shift', 'seq'], 'uq_listing_stage');
        });
    }

    public function down(): void
    {
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropUnique('uq_listing_stage');
        });
    }
};
