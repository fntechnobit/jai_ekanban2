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
        Schema::create('master_machine_conveyor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_id');
            $table->unsignedBigInteger('conveyor_id');

            // Foreign keys
            $table->foreign('machine_id')->references('id')->on('master_machine')->onDelete('cascade');
            $table->foreign('conveyor_id')->references('id')->on('master_conveyor')->onDelete('cascade');

            // Unique constraint to prevent duplicate machine-conveyor pairs
            $table->unique(['machine_id', 'conveyor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_machine_conveyor');
    }
};
