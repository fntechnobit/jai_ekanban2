<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\GroupMenuAccess;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Add "History Print Cutting" right below "Print Cutting" and
     * "History Print Shikake" right below "Print Shikake" inside the
     * eKanban Print menu.
     */
    public function up(): void
    {
        $parent = Menu::where('code', 'ekanban_print')->first();

        if (!$parent) {
            return;
        }

        $newMenus = [
            'ekanban_cutting' => [
                'code' => 'ekanban_cutting_history',
                'name' => 'History Print Cutting',
                'url'  => '/schedule/ekanban-circuit/history',
                'icon' => 'fa-solid fa-clock-rotate-left',
            ],
            'ekanban_shikake' => [
                'code' => 'ekanban_shikake_history',
                'name' => 'History Print Shikake',
                'url'  => '/schedule/ekanban-shikake/history',
                'icon' => 'fa-solid fa-clock-rotate-left',
            ],
        ];

        foreach ($newMenus as $afterCode => $data) {
            $afterMenu = Menu::where('code', $afterCode)->where('parent_id', $parent->id)->first();

            if (!$afterMenu) {
                continue;
            }

            // Make room directly below the print menu it belongs to
            Menu::where('parent_id', $parent->id)
                ->where('order', '>', $afterMenu->order)
                ->increment('order');

            $historyMenu = Menu::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'      => $data['name'],
                    'url'       => $data['url'],
                    'icon'      => $data['icon'],
                    'parent_id' => $parent->id,
                    'order'     => $afterMenu->order + 1,
                    'is_active' => true,
                ]
            );

            // Mirror the access of the print menu it sits under - without a
            // group_menu_access row the menu stays hidden for everyone.
            $accesses = GroupMenuAccess::where('menu_id', $afterMenu->id)->get();

            foreach ($accesses as $access) {
                GroupMenuAccess::updateOrCreate(
                    ['group_id' => $access->group_id, 'menu_id' => $historyMenu->id],
                    [
                        'can_create' => false,
                        'can_read'   => $access->can_read,
                        'can_update' => false,
                        'can_delete' => false,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $parent = Menu::where('code', 'ekanban_print')->first();
        $menus = Menu::whereIn('code', ['ekanban_cutting_history', 'ekanban_shikake_history'])->get();

        foreach ($menus as $menu) {
            GroupMenuAccess::where('menu_id', $menu->id)->delete();
            $order = $menu->order;
            $menu->delete();

            // Close the gap left behind so the sibling ordering stays contiguous
            if ($parent) {
                Menu::where('parent_id', $parent->id)
                    ->where('order', '>', $order)
                    ->decrement('order');
            }
        }
    }
};
