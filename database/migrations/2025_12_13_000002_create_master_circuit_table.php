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
        Schema::create('master_circuit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conveyor_id')->nullable()->constrained('master_conveyor')->onDelete('set null');
            $table->string('conveyor')->nullable();
            $table->string('cct_no')->nullable();
            $table->string('family')->nullable();
            $table->integer('qty')->nullable();
            $table->string('issue')->nullable();
            $table->string('machine')->nullable();
            $table->integer('sequence')->nullable();
            $table->string('barcode')->nullable();
            $table->string('kanban')->nullable();
            $table->date('released_date')->nullable();
            $table->text('released_note')->nullable();
            $table->string('cust_no')->nullable();
            $table->string('barcode_mesin')->nullable();
            $table->string('address')->nullable();
            $table->string('cct_code')->nullable();
            $table->string('kind')->nullable();
            $table->string('size')->nullable();
            $table->string('col')->nullable();
            $table->string('c_l')->nullable();
            $table->string('terminal_1')->nullable();
            $table->string('note_1')->nullable();
            $table->string('gold_1')->nullable();
            $table->string('strip_1')->nullable();
            $table->string('acc_1')->nullable();
            $table->string('acc_1a')->nullable();
            $table->string('tube_1')->nullable();
            $table->string('mark_1')->nullable();
            $table->text('remark_1')->nullable();
            $table->string('terminal_2')->nullable();
            $table->string('note_2')->nullable();
            $table->string('gold_2')->nullable();
            $table->string('strip_2')->nullable();
            $table->string('acc_2')->nullable();
            $table->string('acc_2a')->nullable();
            $table->string('tube_2')->nullable();
            $table->string('mark_2')->nullable();
            $table->text('remark_2')->nullable();
            $table->string('ta')->nullable();
            $table->string('tb')->nullable();
            $table->string('t01')->nullable();
            $table->string('t02')->nullable();
            $table->string('t03')->nullable();
            $table->string('t04')->nullable();
            $table->string('t05')->nullable();
            $table->string('t06')->nullable();
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
        Schema::dropIfExists('master_circuit');
    }
};
