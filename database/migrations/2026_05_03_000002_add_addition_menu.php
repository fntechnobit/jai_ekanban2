<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Add Addition menu to the sidebar (below Defect menu).
     */
    public function up(): void
    {
        // Place Addition right after the Defect menu
        $defectMenu = Menu::where('code', 'defect')->first();
        $afterOrder = $defectMenu ? $defectMenu->order : (Menu::whereNull('parent_id')->max('order') ?? 0);

        // Shift all menus that come after the insertion point
        Menu::whereNull('parent_id')
            ->where('order', '>', $afterOrder)
            ->increment('order');

        $additionMenu = Menu::create([
            'code'      => 'addition',
            'name'      => 'Addition',
            'url'       => '#',
            'icon'      => 'fa-solid fa-circle-plus',
            'parent_id' => null,
            'order'     => $afterOrder + 1,
            'is_active' => true,
        ]);

        $submenus = [
            [
                'code'      => 'addition_cutting',
                'name'      => 'Add Cutting',
                'url'       => '/addition/cutting',
                'icon'      => 'fa-solid fa-scissors',
                'parent_id' => $additionMenu->id,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'addition_shikake',
                'name'      => 'Add Shikake',
                'url'       => '/addition/shikake',
                'icon'      => 'fa-solid fa-wrench',
                'parent_id' => $additionMenu->id,
                'order'     => 2,
                'is_active' => true,
            ],
            [
                'code'      => 'addition_history',
                'name'      => 'Add History',
                'url'       => '/addition/history',
                'icon'      => 'fa-solid fa-clock-rotate-left',
                'parent_id' => $additionMenu->id,
                'order'     => 3,
                'is_active' => true,
            ],
        ];

        foreach ($submenus as $data) {
            Menu::create($data);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $additionMenu = Menu::where('code', 'addition')->first();

        if ($additionMenu) {
            Menu::where('parent_id', $additionMenu->id)->delete();
            $additionMenu->delete();
        }
    }
};
