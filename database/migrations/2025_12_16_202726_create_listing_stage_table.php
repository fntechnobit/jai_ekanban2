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
        Schema::create('listing_stage', function (Blueprint $table) {
            $table->id();
            $table->dateTime('listing_date_time');
            $table->string('conveyor');
            $table->integer('shift');
            $table->string('assycode');
            $table->string('assy');
            $table->integer('qty');
            $table->integer('seq');
            $table->integer('plt');
            $table->integer('mode');
            $table->integer('snp');
            $table->integer('snpa');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_stage');
    }
};
