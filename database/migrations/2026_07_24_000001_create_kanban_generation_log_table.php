<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generation ledger — one row per (conveyor, item, date, shift) generation event.
     *
     * Stores the EXACT figures produced by the carry-over engine so that:
     *  1. reverseBalanceForScheduleGroup() can undo a generation precisely
     *     (sisa -= delta) instead of reconstructing kebutuhan from kanban rows,
     *     which silently drops cutoffs served entirely from carry-over (the
     *     "balance CCT kurang tepat" bug).
     *  2. the balance history report can show, per day, how much listing demand
     *     (kebutuhan) and kanban production (produced) moved the balance.
     *
     * delta = produced - kebutuhan = sisa_after - sisa_before.
     */
    public function up(): void
    {
        Schema::create('kanban_generation_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conveyor_id');
            $table->enum('item_type', ['circuit', 'shikake']);
            $table->unsignedBigInteger('master_id'); // master_circuit_id or master_shikake_id

            $table->date('schedule_date');
            $table->integer('shift');

            // Carry-over figures captured at generation time
            $table->integer('kanban_count')->default(0); // number of kanban tickets opened
            $table->integer('lot')->default(0);          // qty per kanban (master qty)
            $table->integer('produced')->default(0);     // kanban_count * lot
            $table->integer('kebutuhan')->default(0);    // total listing demand consumed (all cutoffs)
            $table->integer('delta')->default(0);        // produced - kebutuhan (signed)
            $table->integer('sisa_before')->default(0);
            $table->integer('sisa_after')->default(0);

            $table->timestamps();

            // One current generation record per schedule group per item
            $table->unique(
                ['conveyor_id', 'item_type', 'master_id', 'schedule_date', 'shift'],
                'unique_generation_group'
            );

            $table->index(['schedule_date', 'conveyor_id'], 'idx_gen_date_conveyor');
            $table->index(['item_type', 'master_id'], 'idx_gen_item');

            $table->foreign('conveyor_id')
                  ->references('id')->on('master_conveyor')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kanban_generation_log');
    }
};
