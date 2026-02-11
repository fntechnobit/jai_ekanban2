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
        Schema::create('master_family_conveyor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('family_id');
            $table->timestamps();

            $table->foreign('conveyor_id')->references('id')->on('master_conveyor')->onDelete('cascade');
            $table->foreign('family_id')->references('id')->on('master_family')->onDelete('cascade');
            
            $table->unique(['conveyor_id', 'family_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_family_conveyor');
    }
};
