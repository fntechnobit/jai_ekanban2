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
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dateTime('listing_date_time')->after('id');
            $table->string('conveyor')->after('listing_date_time');
            $table->integer('shift')->after('conveyor');
            $table->string('assycode')->after('shift');
            $table->string('assy')->after('assycode');
            $table->integer('qty')->after('assy');
            $table->integer('seq')->after('qty');
            $table->integer('plt')->after('seq');
            $table->integer('mode')->after('plt');
            $table->integer('snp')->after('mode');
            $table->integer('snpa')->after('snp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropColumn([
                'listing_date_time',
                'conveyor',
                'shift',
                'assycode',
                'assy',
                'qty',
                'seq',
                'plt',
                'mode',
                'snp',
                'snpa',
            ]);
        });
    }
};
