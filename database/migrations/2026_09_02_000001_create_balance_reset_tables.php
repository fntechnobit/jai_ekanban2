<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pendukung fitur "Samakan Saldo Kanban".
 *
 * balance_reset_log      — satu baris per penyamaan: cakupan, tanggal acuan, hasil.
 * balance_reset_snapshot — nilai saldo SEBELUM ditimpa, satu baris per item,
 *                          supaya penyamaan dapat dibatalkan tanpa restore database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_reset_log', function (Blueprint $table) {
            $table->id();

            // Kondisi saldo sumber diambil pada AKHIR tanggal ini.
            $table->date('cutoff_date');

            // Conveyor yang ikut disamakan (array id, disimpan sebagai JSON).
            $table->json('conveyor_ids');

            // Database sumber yang dibaca, dicatat apa adanya untuk jejak audit.
            $table->string('reference_db', 100)->nullable();

            $table->unsignedInteger('circuits_updated')->default(0);
            $table->unsignedInteger('shikakes_updated')->default(0);
            $table->unsignedInteger('kanban_deleted')->default(0);
            $table->unsignedInteger('schedules_unverified')->default(0);

            // applied = sudah ditulis · undone = sudah dibatalkan kembali
            $table->enum('status', ['applied', 'undone'])->default('applied');

            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('undone_at')->nullable();
            $table->foreignId('undone_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cutoff_date', 'status']);
        });

        Schema::create('balance_reset_snapshot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reset_log_id')->constrained('balance_reset_log')->cascadeOnDelete();

            $table->enum('item_type', ['circuit', 'shikake']);
            $table->unsignedBigInteger('conveyor_id');
            $table->unsignedBigInteger('master_id');

            $table->integer('sisa_before');
            $table->integer('sisa_after');
            $table->integer('nomor_urut_before');
            $table->integer('nomor_urut_after');

            $table->timestamps();

            $table->index(['reset_log_id', 'item_type']);
            $table->index(['conveyor_id', 'master_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_reset_snapshot');
        Schema::dropIfExists('balance_reset_log');
    }
};
