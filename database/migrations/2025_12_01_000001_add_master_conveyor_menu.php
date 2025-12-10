<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

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
        $masterConveyor = Menu::create([
            'code' => 'master_conveyor',
            'name' => 'Conveyor Data',
            'url' => '/master-data/master-conveyor',
            'icon' => 'fas fa-conveyor-belt-boxes',
            'parent_id' => $masterDataMenu->id,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        // Grant permissions to Super Admin (group_id = 1)
        GroupMenuAccess::create([
            'group_id' => 1,
            'menu_id' => $masterConveyor->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $menu = Menu::where('code', 'master_conveyor')->first();
        if ($menu) {
            GroupMenuAccess::where('menu_id', $menu->id)->delete();
            $menu->delete();
        }
    }
};
