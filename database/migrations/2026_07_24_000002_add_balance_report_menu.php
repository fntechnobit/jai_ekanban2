<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

return new class extends Migration
{
    /**
     * Add Report > Balance History menu (placed after Addition).
     */
    public function up(): void
    {
        $additionMenu = Menu::where('code', 'addition')->first();
        $afterOrder = $additionMenu ? $additionMenu->order : (Menu::whereNull('parent_id')->max('order') ?? 0);

        // Shift menus after the insertion point to make room
        Menu::whereNull('parent_id')
            ->where('order', '>', $afterOrder)
            ->increment('order');

        $reportMenu = Menu::create([
            'code'      => 'report',
            'name'      => 'Report',
            'url'       => '#',
            'icon'      => 'fa-solid fa-chart-line',
            'parent_id' => null,
            'order'     => $afterOrder + 1,
            'is_active' => true,
        ]);

        $balanceMenu = Menu::create([
            'code'      => 'report_balance',
            'name'      => 'Balance History',
            'url'       => '/report/balance',
            'icon'      => 'fa-solid fa-scale-balanced',
            'parent_id' => $reportMenu->id,
            'order'     => 1,
            'is_active' => true,
        ]);

        // Grant read access to every group that can already read the Addition menu
        // (same audience). Without a group_menu_access row the menu is hidden.
        if ($additionMenu) {
            $groupIds = GroupMenuAccess::where('menu_id', $additionMenu->id)
                ->where('can_read', true)
                ->pluck('group_id')
                ->unique();

            foreach ($groupIds as $groupId) {
                foreach ([$reportMenu->id, $balanceMenu->id] as $menuId) {
                    GroupMenuAccess::updateOrCreate(
                        ['group_id' => $groupId, 'menu_id' => $menuId],
                        ['can_create' => false, 'can_read' => true, 'can_update' => false, 'can_delete' => false]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $reportMenu = Menu::where('code', 'report')->first();

        if ($reportMenu) {
            Menu::where('parent_id', $reportMenu->id)->delete();
            $reportMenu->delete();
        }
    }
};
