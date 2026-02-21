<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Safe to re-run: uses updateOrCreate so existing records are updated.
     * Old legacy menus (ekanban_shikake as parent, ekanban_circuit) are cleaned up.
     */
    public function run(): void
    {
        // ── Step 1: Remove legacy menus that no longer exist in the new structure ─────
        $legacyCodes = [
            'ekanban_shikake_print_machine',  // old child "Print Per Machine" under eKanban Shikake
            'ekanban_circuit_print_machine',  // old child "Print Per Machine" under eKanban Cutting
            'ekanban_circuit',                // old parent "eKanban Cutting"
        ];
        foreach ($legacyCodes as $legacyCode) {
            $old = Menu::where('code', $legacyCode)->first();
            if ($old) {
                GroupMenuAccess::where('menu_id', $old->id)->delete();
                $old->delete();
            }
        }

        // ── Step 2: Upsert top-level parent menus (order matters for sidebar) ─────────
        // Order: Dashboard(1) > Schedule(2) > eKanban Print(3) > Defect(4) > Master Data(5) > System(6)
        $parentMenus = [
            [
                'code'      => 'dashboard',
                'name'      => 'Dashboard',
                'url'       => '/dashboard',
                'icon'      => 'fa-solid fa-gauge-high',
                'parent_id' => null,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'schedule',
                'name'      => 'Schedule',
                'url'       => '#',
                'icon'      => 'fa-solid fa-calendar-days',
                'parent_id' => null,
                'order'     => 2,
                'is_active' => true,
            ],
            [
                'code'      => 'ekanban_print',
                'name'      => 'eKanban Print',
                'url'       => '#',
                'icon'      => 'fa-solid fa-print',
                'parent_id' => null,
                'order'     => 3,
                'is_active' => true,
            ],
            [
                'code'      => 'defect',
                'name'      => 'Defect',
                'url'       => '#',
                'icon'      => 'fa-solid fa-triangle-exclamation',
                'parent_id' => null,
                'order'     => 4,
                'is_active' => true,
            ],
            [
                'code'      => 'master_data',
                'name'      => 'Master Data',
                'url'       => '#',
                'icon'      => 'fa-solid fa-database',
                'parent_id' => null,
                'order'     => 5,
                'is_active' => true,
            ],
            [
                'code'      => 'system',
                'name'      => 'System',
                'url'       => '#',
                'icon'      => 'fa-solid fa-gear',
                'parent_id' => null,
                'order'     => 6,
                'is_active' => true,
            ],
        ];

        foreach ($parentMenus as $menuData) {
            $menu = Menu::updateOrCreate(['code' => $menuData['code']], $menuData);
            GroupMenuAccess::firstOrCreate(
                ['group_id' => 1, 'menu_id' => $menu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }

        // ── Step 3: Schedule submenus ─────────────────────────────────────────────────
        $scheduleMenu = Menu::where('code', 'schedule')->first();
        $scheduleSubmenus = [
            [
                'code'      => 'assy_scheduler',
                'name'      => 'Assy Scheduler',
                'url'       => '/schedule/assy-scheduler',
                'icon'      => 'fa-solid fa-calendar-check',
                'parent_id' => $scheduleMenu->id,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'schedule_verification',
                'name'      => 'Verification',
                'url'       => '/schedule/schedule-verification',
                'icon'      => 'fa-solid fa-clipboard-check',
                'parent_id' => $scheduleMenu->id,
                'order'     => 2,
                'is_active' => true,
            ],
        ];
        $this->upsertSubmenus($scheduleSubmenus);

        // ── Step 4: eKanban Print submenus ───────────────────────────────────────────
        // Print Cutting first (order 1), Print Shikake second (order 2)
        // Note: 'ekanban_shikake' code is reused — the old parent record will be
        //       updated to become a child of ekanban_print via updateOrCreate.
        $ekanbanPrintMenu = Menu::where('code', 'ekanban_print')->first();
        $printSubmenus = [
            [
                'code'      => 'ekanban_cutting',
                'name'      => 'Print Cutting',
                'url'       => '/schedule/ekanban-circuit/print-machine',
                'icon'      => 'fa-solid fa-diagram-project',
                'parent_id' => $ekanbanPrintMenu->id,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'ekanban_shikake',
                'name'      => 'Print Shikake',
                'url'       => '/schedule/ekanban-shikake/print-machine',
                'icon'      => 'fa-solid fa-table',
                'parent_id' => $ekanbanPrintMenu->id,
                'order'     => 2,
                'is_active' => true,
            ],
        ];
        $this->upsertSubmenus($printSubmenus);

        // ── Step 5: Defect submenus ───────────────────────────────────────────────────
        $defectMenu = Menu::where('code', 'defect')->first();
        $defectSubmenus = [
            [
                'code'      => 'defect_cutting',
                'name'      => 'Defect Cutting',
                'url'       => '/defect/cutting',
                'icon'      => 'fa-solid fa-scissors',
                'parent_id' => $defectMenu->id,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'defect_shikake',
                'name'      => 'Defect Shikake',
                'url'       => '/defect/shikake',
                'icon'      => 'fa-solid fa-table-list',
                'parent_id' => $defectMenu->id,
                'order'     => 2,
                'is_active' => true,
            ],
            [
                'code'      => 'defect_history',
                'name'      => 'Defect History',
                'url'       => '/defect/history',
                'icon'      => 'fa-solid fa-clock-rotate-left',
                'parent_id' => $defectMenu->id,
                'order'     => 3,
                'is_active' => true,
            ],
        ];
        $this->upsertSubmenus($defectSubmenus);

        // ── Step 6: System submenus ───────────────────────────────────────────────────
        $systemMenu = Menu::where('code', 'system')->first();
        $systemSubmenus = [
            [
                'code'      => 'users',
                'name'      => 'Users',
                'url'       => '/system/users',
                'icon'      => 'fa-solid fa-users',
                'parent_id' => $systemMenu->id,
                'order'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'user_groups',
                'name'      => 'User Groups',
                'url'       => '/system/user-groups',
                'icon'      => 'fa-solid fa-users-gear',
                'parent_id' => $systemMenu->id,
                'order'     => 2,
                'is_active' => true,
            ],
            [
                'code'      => 'menus',
                'name'      => 'Menus',
                'url'       => '/system/menus',
                'icon'      => 'fa-solid fa-bars',
                'parent_id' => $systemMenu->id,
                'order'     => 3,
                'is_active' => true,
            ],
            [
                'code'      => 'listing_sync',
                'name'      => 'Synchronize List Assy',
                'url'       => '/system/listing-sync',
                'icon'      => 'fa-solid fa-arrows-rotate',
                'parent_id' => $systemMenu->id,
                'order'     => 4,
                'is_active' => true,
            ],
        ];
        $this->upsertSubmenus($systemSubmenus);
    }

    /**
     * Upsert submenus and ensure Super Admin (group_id=1) has full access.
     */
    private function upsertSubmenus(array $submenus): void
    {
        foreach ($submenus as $submenuData) {
            $menu = Menu::updateOrCreate(['code' => $submenuData['code']], $submenuData);
            GroupMenuAccess::firstOrCreate(
                ['group_id' => 1, 'menu_id' => $menu->id],
                ['can_create' => true, 'can_read' => true, 'can_update' => true, 'can_delete' => true]
            );
        }
    }
}
