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
        Schema::create('master_shikake_twist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->string('cct_code')->nullable();
            $table->string('cct_no')->nullable();
            $table->string('machine_twist')->nullable();
            $table->integer('sequence_2')->nullable();
            $table->string('barcode_navigasi')->nullable();
            $table->string('barcode_process')->nullable();
            $table->string('barcode_shikake')->nullable();
            $table->string('to_store')->nullable();
            $table->string('cust_no')->nullable();
            $table->string('kind')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('cl')->nullable();
            $table->string('terminal_a')->nullable();
            $table->string('acc_1_a')->nullable();
            $table->string('tube_a')->nullable();
            $table->string('note_a')->nullable();
            $table->string('strip_a')->nullable();
            $table->string('mark_a')->nullable();
            $table->string('terminal_b')->nullable();
            $table->string('acc_1_ab')->nullable();
            $table->string('tube_b')->nullable();
            $table->string('note_b')->nullable();
            $table->string('strip_b')->nullable();
            $table->string('mark_b')->nullable();
            $table->date('released_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('master_shikake_id', 'idx_twist_shikake_id');
            $table->index('cct_code', 'idx_twist_cct_code');
            $table->index('cct_no', 'idx_twist_cct_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_twist');
    }
};
