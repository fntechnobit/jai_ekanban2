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
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->unsignedBigInteger('id_listing')->nullable()->after('id');
            $table->timestamp('synced_at')->nullable()->after('snpa');
            
            $table->index('id_listing', 'idx_id_listing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_stage', function (Blueprint $table) {
            $table->dropIndex('idx_id_listing');
            $table->dropColumn('id_listing');
            $table->dropColumn('synced_at');
        });
    }
};
