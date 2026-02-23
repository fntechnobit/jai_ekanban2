<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find Master Data parent menu
        $masterDataMenu = Menu::where('code', 'master_data')->first();
        
        if (!$masterDataMenu) {
            return;
        }

        // Get the max order of existing menus under Master Data
        $maxOrder = Menu::where('parent_id', $masterDataMenu->id)->max('order') ?? 0;

        // Create Master Conveyor menu
        Menu::create([
            'code' => 'master_conveyor',
            'name' => 'Conveyor Data',
            'url' => '/master-data/master-conveyor',
            'icon' => 'fa-solid fa-right-left',
            'parent_id' => $masterDataMenu->id,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menu = Menu::where('code', 'master_conveyor')->first();
        if ($menu) {
            $menu->delete();
        }
    }
};
