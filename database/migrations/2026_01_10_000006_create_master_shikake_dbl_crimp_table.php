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
        Schema::create('master_shikake_dbl_crimp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->string('shield_no')->nullable();
            $table->string('dbl_crimp')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_shikake_id', 'idx_dbl_crimp_shikake_id');
            $table->index('shield_no', 'idx_dbl_crimp_shield_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_dbl_crimp');
    }
};
