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
        Schema::create('master_circuit_assy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_assy_id')->constrained('master_assy')->onDelete('cascade');
            $table->foreignId('master_circuit_id')->constrained('master_circuit')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_circuit_assy');
    }
};
