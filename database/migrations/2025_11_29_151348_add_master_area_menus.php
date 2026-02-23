<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if Master Data menu exists, if not create it
        $masterDataMenu = DB::table('menus')->where('code', 'master_data')->first();

        if (!$masterDataMenu) {
            $masterDataId = DB::table('menus')->insertGetId([
                'code' => 'master_data',
                'name' => 'Master Data',
                'url' => '#',
                'icon' => 'fas fa-database',
                'parent_id' => null,
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $masterDataId = $masterDataMenu->id;
        }

        // Create Master Area menu under Master Data
        DB::table('menus')->insert([
            'code' => 'master_area',
            'name' => 'Area Data',
            'url' => '/master-data/master-area',
            'icon' => 'fa-solid fa-map-location-dot',
            'parent_id' => $masterDataId,
            'order' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menus')->where('code', 'master_area')->delete();
    }
};
