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
        Schema::create('master_shikake_joint', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->string('bonder_no')->nullable();
            $table->string('address')->nullable();
            $table->string('address_store')->nullable();
            $table->string('to_machine')->nullable();
            $table->string('barcode_process')->nullable();
            $table->date('released_date')->nullable();
            // CCT/Bonder pairs (1-5)
            $table->string('cct_no_1')->nullable();
            $table->string('bonder_no_1')->nullable();
            $table->string('cct_no_2')->nullable();
            $table->string('bonder_no_2')->nullable();
            $table->string('cct_no_3')->nullable();
            $table->string('bonder_no_3')->nullable();
            $table->string('cct_no_4')->nullable();
            $table->string('bonder_no_4')->nullable();
            $table->string('cct_no_5')->nullable();
            $table->string('bonder_no_5')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_shikake_id', 'idx_joint_shikake_id');
            $table->index('bonder_no', 'idx_joint_bonder_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_joint');
    }
};
