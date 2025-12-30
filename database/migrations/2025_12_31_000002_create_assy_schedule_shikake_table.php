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
            $table->unsignedBigInteger('assy_schedule_id');
            $table->unsignedBigInteger('shikake_id');
            $table->boolean('is_printed')->default(false);
            $table->timestamp('last_printed_at')->nullable();
            $table->unsignedBigInteger('last_printed_by')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('is_printed');
            $table->index(['assy_schedule_id', 'shikake_id']);
            
            // Unique constraint to prevent duplicate tracking
            $table->unique(['assy_schedule_id', 'shikake_id'], 'unique_schedule_shikake');

            // Foreign keys
            $table->foreign('assy_schedule_id')
                ->references('id')
                ->on('assy_schedule')
                ->onDelete('cascade');
                
            $table->foreign('shikake_id')
                ->references('id')
                ->on('master_shikake')
                ->onDelete('cascade');
                
            $table->foreign('last_printed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
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
