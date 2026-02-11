<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menu codes to be removed
        $menusToRemove = [
            'ekanban_circuit_print_preview',    // Print Preview from Office - eKanban Circuit
            'ekanban_shikake_print_preview',    // Print Preview from Office - eKanban Shikake
        ];

        foreach ($menusToRemove as $menuCode) {
            $menu = Menu::where('code', $menuCode)->first();
            
            if ($menu) {
                // First, remove all group menu access records for this menu
                GroupMenuAccess::where('menu_id', $menu->id)->delete();
                
                // Then remove the menu itself
                $menu->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the removed menus if rollback is needed
        
        // First, get the parent menus
        $ekanbanCircuitMenu = Menu::where('code', 'ekanban_circuit')->first();
        $ekanbanShikakeMenu = Menu::where('code', 'ekanban_shikake')->first();

        // Recreate eKanban Circuit Print Preview menu
        if ($ekanbanCircuitMenu) {
            $circuitPreviewMenu = Menu::firstOrCreate(
                ['code' => 'ekanban_circuit_print_preview'],
                [
                    'name' => 'Print Preview from Office',
                    'url' => '/schedule/ekanban-circuit/print-preview',
                    'icon' => 'fa-solid fa-eye',
                    'parent_id' => $ekanbanCircuitMenu->id,
                    'order' => 2,
                    'is_active' => true,
                ]
            );

            // Create group access for the menu
            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $circuitPreviewMenu->id,
                ],
                [
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );
        }

        // Recreate eKanban Shikake Print Preview menu
        if ($ekanbanShikakeMenu) {
            $shikakePreviewMenu = Menu::firstOrCreate(
                ['code' => 'ekanban_shikake_print_preview'],
                [
                    'name' => 'Print Preview from Office',
                    'url' => '/schedule/ekanban-shikake/print-preview',
                    'icon' => 'fa-solid fa-eye',
                    'parent_id' => $ekanbanShikakeMenu->id,
                    'order' => 2,
                    'is_active' => true,
                ]
            );

            // Create group access for the menu
            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $shikakePreviewMenu->id,
                ],
                [
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );
        }
    }
};