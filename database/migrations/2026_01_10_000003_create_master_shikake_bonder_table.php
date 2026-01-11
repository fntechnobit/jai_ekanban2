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
        Schema::create('master_shikake_bonder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->string('bonder_no')->nullable();
            $table->string('address')->nullable();
            $table->string('dies')->nullable();
            $table->string('to_machine')->nullable();
            $table->string('barcode_navigasi')->nullable();
            $table->string('barcode_process')->nullable();
            $table->date('released_date')->nullable();
            // Side A CCT/Bonder pairs (1-7)
            $table->string('cct_no_a_1')->nullable();
            $table->string('bonder_no_a_1')->nullable();
            $table->string('cct_no_a_2')->nullable();
            $table->string('bonder_no_a_2')->nullable();
            $table->string('cct_no_a_3')->nullable();
            $table->string('bonder_no_a_3')->nullable();
            $table->string('cct_no_a_4')->nullable();
            $table->string('bonder_no_a_4')->nullable();
            $table->string('cct_no_a_5')->nullable();
            $table->string('bonder_no_a_5')->nullable();
            $table->string('cct_no_a_6')->nullable();
            $table->string('bonder_no_a_6')->nullable();
            $table->string('cct_no_a_7')->nullable();
            $table->string('bonder_no_a_7')->nullable();
            // Side B CCT/Bonder pairs (1-7)
            $table->string('cct_no_b_1')->nullable();
            $table->string('bonder_no_b_1')->nullable();
            $table->string('cct_no_b_2')->nullable();
            $table->string('bonder_no_b_2')->nullable();
            $table->string('cct_no_b_3')->nullable();
            $table->string('bonder_no_b_3')->nullable();
            $table->string('cct_no_b_4')->nullable();
            $table->string('bonder_no_b_4')->nullable();
            $table->string('cct_no_b_5')->nullable();
            $table->string('bonder_no_b_5')->nullable();
            $table->string('cct_no_b_6')->nullable();
            $table->string('bonder_no_b_6')->nullable();
            $table->string('cct_no_b_7')->nullable();
            $table->string('bonder_no_b_7')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_shikake_id', 'idx_bonder_shikake_id');
            $table->index('bonder_no', 'idx_bonder_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_bonder');
    }
};
