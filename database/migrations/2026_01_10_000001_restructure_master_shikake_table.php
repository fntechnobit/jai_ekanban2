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
        // Drop existing related tables first
        Schema::dropIfExists('assy_schedule_shikake');
        Schema::dropIfExists('master_shikake_assy');
        Schema::dropIfExists('master_shikake');

        // Recreate master_shikake with new simplified structure
        Schema::create('master_shikake', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conveyor_id')->nullable()->constrained('master_conveyor')->onDelete('set null');
            $table->string('process')->nullable();
            $table->string('conveyor')->nullable();
            $table->string('machine')->nullable();
            $table->integer('qty')->nullable();
            $table->string('issue')->nullable();
            $table->string('barcode_kanban')->nullable();
            $table->string('family')->nullable();
            $table->text('released_note')->nullable();
            $table->integer('sequence')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // Add indexes for performance
            $table->index('conveyor_id', 'idx_conveyor_id');
            $table->index('machine', 'idx_machine');
            $table->index(['conveyor_id', 'machine'], 'idx_conveyor_machine');
            $table->index('process', 'idx_process');
        });

        // Recreate master_shikake_assy pivot table
        Schema::create('master_shikake_assy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_shikake_id')->constrained('master_shikake')->onDelete('cascade');
            $table->foreignId('master_assy_id')->constrained('master_assy')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['master_shikake_id', 'master_assy_id'], 'unique_shikake_assy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_shikake_assy');
        Schema::dropIfExists('master_shikake');
    }
};
