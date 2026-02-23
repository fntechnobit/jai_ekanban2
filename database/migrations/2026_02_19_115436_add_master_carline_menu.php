<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the Master Data parent menu
        $masterDataMenu = DB::table('menus')->where('code', 'master_data')->first();

        if ($masterDataMenu) {
            // Create Carline menu under Master Data (order 2, after Area which is order 1)
            DB::table('menus')->insert([
                'code' => 'master_carline',
                'name' => 'Carline Data',
                'url' => 'master-data/master-carline',
                'icon' => 'fa-solid fa-road',
                'parent_id' => $masterDataMenu->id,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update Family menu order to 3 (after Carline)
            DB::table('menus')
                ->where('code', 'master_family')
                ->update(['order' => 3]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete the carline menu
        DB::table('menus')->where('code', 'master_carline')->delete();

        // Restore Family menu order to 2
        DB::table('menus')
            ->where('code', 'master_family')
            ->update(['order' => 2]);
    }
};
