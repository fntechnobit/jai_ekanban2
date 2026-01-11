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
        Schema::create('master_shikake_shield', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->string('shield_no')->nullable();
            $table->string('address')->nullable();
            $table->string('blade')->nullable();
            $table->string('cct_no_1')->nullable();
            $table->string('bonder_no_1')->nullable();
            $table->string('cct_no_2')->nullable();
            $table->string('bonder_no_2')->nullable();
            // To fields (1-9)
            $table->string('to_1')->nullable();
            $table->string('to_2')->nullable();
            $table->string('to_3')->nullable();
            $table->string('to_4')->nullable();
            $table->string('to_5')->nullable();
            $table->string('to_6')->nullable();
            $table->string('to_7')->nullable();
            $table->string('to_8')->nullable();
            $table->string('to_9')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_shikake_id', 'idx_shield_shikake_id');
            $table->index('shield_no', 'idx_shield_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_shield');
    }
};
