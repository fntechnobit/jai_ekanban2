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
        Schema::create('assy_schedule', function (Blueprint $table) {
            $table->id();
            $table->dateTime('schedule');
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('listing_id');
            $table->integer('shift');
            $table->string('assycode');
            $table->string('assy');
            $table->integer('qty');
            $table->integer('seq');
            $table->integer('mode');
            $table->integer('snp');
            $table->integer('snpa');
            $table->tinyInteger('is_lock')->default(0);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign keys
            $table->foreign('conveyor_id')->references('id')->on('master_conveyor')->onDelete('cascade');
            $table->foreign('listing_id')->references('id')->on('listing_stage')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assy_schedule');
    }
};
