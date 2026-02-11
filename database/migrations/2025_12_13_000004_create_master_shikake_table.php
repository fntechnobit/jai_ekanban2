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
        Schema::create('master_shikake', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conveyor_id')->nullable()->constrained('master_conveyor')->onDelete('set null');
            $table->string('conveyor')->nullable();
            $table->string('shikake_no')->nullable();
            $table->string('family')->nullable();
            $table->integer('qty')->nullable();
            $table->string('issue')->nullable();
            $table->string('machine')->nullable();
            $table->integer('sequence')->nullable();
            $table->string('barcode_kanban')->nullable();
            $table->date('released_date')->nullable();
            $table->text('released_note')->nullable();
            $table->string('store')->nullable();
            $table->string('barcode_mesin')->nullable();
            $table->string('address')->nullable();
            $table->string('cct_a')->nullable();
            $table->string('address_a')->nullable();
            $table->string('cct_b')->nullable();
            $table->string('address_b')->nullable();
            $table->string('cct_c')->nullable();
            $table->string('address_c')->nullable();
            $table->string('cct_4')->nullable();
            $table->string('address_4')->nullable();
            $table->string('cct_5')->nullable();
            $table->string('address_5')->nullable();
            $table->string('cct_6')->nullable();
            $table->string('address_6')->nullable();
            $table->string('cct_7')->nullable();
            $table->string('address_7')->nullable();
            $table->string('barcode_proses')->nullable();
            $table->string('barcode_navigasi')->nullable();
            $table->string('dies')->nullable();
            $table->integer('jumlah_kombinasi')->nullable();
            $table->string('blade')->nullable();
            $table->string('t01')->nullable();
            $table->string('t02')->nullable();
            $table->string('t03')->nullable();
            $table->string('t04')->nullable();
            $table->string('t05')->nullable();
            $table->string('t06')->nullable();
            $table->string('t07')->nullable();
            $table->string('t08')->nullable();
            $table->string('t09')->nullable();
            $table->string('joint')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake');
    }
};
