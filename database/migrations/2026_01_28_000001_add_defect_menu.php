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
        // Get the max order of root level menus
        $maxOrder = Menu::whereNull('parent_id')->max('order') ?? 0;

        // Create Defect parent menu
        $defectMenu = Menu::create([
            'code' => 'defect',
            'name' => 'Defect',
            'url' => '#',
            'icon' => 'fa-solid fa-triangle-exclamation',
            'parent_id' => null,
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        // Grant permissions to Super Admin (group_id = 1)
        GroupMenuAccess::create([
            'group_id' => 1,
            'menu_id' => $defectMenu->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
        ]);

        // Create Defect submenus
        $submenus = [
            [
                'code' => 'defect_cutting',
                'name' => 'Defect Cutting',
                'url' => '/defect/cutting',
                'icon' => 'fa-solid fa-scissors',
                'parent_id' => $defectMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'defect_shikake',
                'name' => 'Defect Shikake',
                'url' => '/defect/shikake',
                'icon' => 'fa-solid fa-wrench',
                'parent_id' => $defectMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'defect_history',
                'name' => 'Defect History',
                'url' => '/defect/history',
                'icon' => 'fa-solid fa-clock-rotate-left',
                'parent_id' => $defectMenu->id,
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($submenus as $submenuData) {
            $submenu = Menu::create($submenuData);

            // Grant permissions to Super Admin (group_id = 1)
            GroupMenuAccess::create([
                'group_id' => 1,
                'menu_id' => $submenu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all defect submenus
        $defectMenu = Menu::where('code', 'defect')->first();
        
        if ($defectMenu) {
            // Delete all submenus and their access rights
            $submenus = Menu::where('parent_id', $defectMenu->id)->get();
            foreach ($submenus as $submenu) {
                GroupMenuAccess::where('menu_id', $submenu->id)->delete();
                $submenu->delete();
            }

            // Delete parent menu and its access rights
            GroupMenuAccess::where('menu_id', $defectMenu->id)->delete();
            $defectMenu->delete();
        }
    }
};
