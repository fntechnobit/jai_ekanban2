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
     */
    public function run(): void
    {
        $menus = [
            [
                'code' => 'dashboard',
                'name' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'fa-solid fa-gauge-high',
                'parent_id' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'schedule',
                'name' => 'Schedule',
                'url' => '#',
                'icon' => 'fa-solid fa-calendar-days',
                'parent_id' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_print',
                'name' => 'eKanban Print',
                'url' => '#',
                'icon' => 'fa-solid fa-print',
                'parent_id' => null,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'defect',
                'name' => 'Defect',
                'url' => '#',
                'icon' => 'fa-solid fa-triangle-exclamation',
                'parent_id' => null,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'master_data',
                'name' => 'Master Data',
                'url' => '#',
                'icon' => 'fa-solid fa-database',
                'parent_id' => null,
                'order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'system',
                'name' => 'System',
                'url' => '#',
                'icon' => 'fa-solid fa-gear',
                'parent_id' => null,
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($menus as $menuData) {
            $menu = Menu::firstOrCreate(
                ['code' => $menuData['code']],
                $menuData
            );

            // Grant permissions to Super Admin (group_id = 1)
            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $menu->id,
                ],
                [
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );
        }

        // Create submenu for System
        $systemMenu = Menu::where('code', 'system')->first();
        
        $submenus = [
            [
                'code' => 'users',
                'name' => 'Users',
                'url' => '/system/users',
                'icon' => 'fa-solid fa-users',
                'parent_id' => $systemMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'user_groups',
                'name' => 'User Groups',
                'url' => '/system/user-groups',
                'icon' => 'fa-solid fa-users-gear',
                'parent_id' => $systemMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'menus',
                'name' => 'Menus',
                'url' => '/system/menus',
                'icon' => 'fa-solid fa-bars',
                'parent_id' => $systemMenu->id,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'listing_sync',
                'name' => 'Synchronize List Assy',
                'url' => '/system/listing-sync',
                'icon' => 'fa-solid fa-arrows-rotate',
                'parent_id' => $systemMenu->id,
                'order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($submenus as $submenu) {
            $menu = Menu::firstOrCreate(
                ['code' => $submenu['code']],
                $submenu
            );

            // Grant permissions to Super Admin (group_id = 1)
            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $menu->id,
                ],
                [
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );
        }

        // Create submenu for Schedule
        $scheduleMenu = Menu::where('code', 'schedule')->first();
        
        $scheduleSubmenus = [
            [
                'code' => 'assy_scheduler',
                'name' => 'Assy Scheduler',
                'url' => '/schedule/assy-scheduler',
                'icon' => 'fa-solid fa-calendar-check',
                'parent_id' => $scheduleMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'schedule_verification',
                'name' => 'Verification',
                'url' => '/schedule/schedule-verification',
                'icon' => 'fa-solid fa-clipboard-check',
                'parent_id' => $scheduleMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($scheduleSubmenus as $submenu) {
            $menu = Menu::firstOrCreate(
                ['code' => $submenu['code']],
                $submenu
            );

            // Grant permissions to Super Admin (group_id = 1)
            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $menu->id,
                ],
                [
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => true,
                ]
            );
        }

        // Create submenus for eKanban Print
        $ekanbanPrintMenu = Menu::where('code', 'ekanban_print')->first();
        $printSubmenus = [
            [
                'code' => 'ekanban_shikake',
                'name' => 'Shikake',
                'url' => '/schedule/ekanban-shikake/print-machine',
                'icon' => 'fa-solid fa-table',
                'parent_id' => $ekanbanPrintMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_cutting',
                'name' => 'Cutting',
                'url' => '/schedule/ekanban-circuit/print-machine',
                'icon' => 'fa-solid fa-diagram-project',
                'parent_id' => $ekanbanPrintMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($printSubmenus as $submenu) {
            $menu = Menu::firstOrCreate(
                ['code' => $submenu['code']],
                $submenu
            );

            GroupMenuAccess::firstOrCreate(
                [
                    'group_id' => 1,
                    'menu_id' => $menu->id,
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
}
