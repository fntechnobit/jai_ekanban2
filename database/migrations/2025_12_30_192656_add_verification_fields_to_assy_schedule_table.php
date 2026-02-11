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
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('is_lock');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified_at', 'verified_by']);
        });
    }
};
