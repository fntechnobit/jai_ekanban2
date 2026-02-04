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
            $table->boolean('is_user_edited')->default(false)->after('updated_by')->comment('Flag to indicate if user manually edited this record');
            $table->softDeletes()->after('is_user_edited')->comment('Soft delete timestamp for user-deleted records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assy_schedule', function (Blueprint $table) {
            $table->dropColumn('is_user_edited');
            $table->dropSoftDeletes();
        });
    }
};
