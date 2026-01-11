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
        Schema::create('assy_schedule_shikake', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assy_schedule_id')->constrained('assy_schedule')->onDelete('cascade');
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->boolean('is_printed')->default(false);
            $table->integer('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('assy_schedule_id', 'idx_assy_schedule_shikake');
            $table->index('master_shikake_id', 'idx_master_shikake');
            $table->index('is_printed', 'idx_shikake_is_printed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assy_schedule_shikake');
    }
};
