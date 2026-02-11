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
        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            $table->unsignedBigInteger('last_printed_by')->nullable()->after('last_printed_at');
            
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
        Schema::table('assy_schedule_shikake', function (Blueprint $table) {
            $table->dropForeign(['last_printed_by']);
            $table->dropColumn('last_printed_by');
        });
    }
};
