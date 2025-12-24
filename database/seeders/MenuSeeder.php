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
                'icon' => 'fas fa-tachometer-alt',
                'parent_id' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'system',
                'name' => 'System',
                'url' => '#',
                'icon' => 'fas fa-cog',
                'parent_id' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'schedule',
                'name' => 'Schedule',
                'url' => '#',
                'icon' => 'fas fa-calendar-alt',
                'parent_id' => null,
                'order' => 3,
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
                'icon' => 'fas fa-users',
                'parent_id' => $systemMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'user_groups',
                'name' => 'User Groups',
                'url' => '/system/user-groups',
                'icon' => 'fas fa-users-cog',
                'parent_id' => $systemMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'menus',
                'name' => 'Menus',
                'url' => '/system/menus',
                'icon' => 'fas fa-bars',
                'parent_id' => $systemMenu->id,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'listing_sync',
                'name' => 'Synchronize List Assy',
                'url' => '/system/listing-sync',
                'icon' => 'fas fa-sync-alt',
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
                'icon' => 'fas fa-calendar-check',
                'parent_id' => $scheduleMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_circuit',
                'name' => 'eKanban Circuit',
                'url' => '#',
                'icon' => 'fas fa-route',
                'parent_id' => $scheduleMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_shikake',
                'name' => 'eKanban Shikake',
                'url' => '#',
                'icon' => 'fas fa-wrench',
                'parent_id' => $scheduleMenu->id,
                'order' => 3,
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

        // Create sub-sub menus for eKanban Circuit
        $ekanbanCircuitMenu = Menu::where('code', 'ekanban_circuit')->first();
        $circuitSubmenus = [
            [
                'code' => 'ekanban_circuit_print_machine',
                'name' => 'Print Per Machine',
                'url' => '/schedule/ekanban-circuit/print-machine',
                'icon' => 'fas fa-print',
                'parent_id' => $ekanbanCircuitMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_circuit_print_preview',
                'name' => 'Print Preview from Office',
                'url' => '/schedule/ekanban-circuit/print-preview',
                'icon' => 'fas fa-eye',
                'parent_id' => $ekanbanCircuitMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($circuitSubmenus as $submenu) {
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

        // Create sub-sub menus for eKanban Shikake  
        $ekanbanShikakeMenu = Menu::where('code', 'ekanban_shikake')->first();
        $shikakeSubmenus = [
            [
                'code' => 'ekanban_shikake_print_machine',
                'name' => 'Print Per Machine',
                'url' => '/schedule/ekanban-shikake/print-machine',
                'icon' => 'fas fa-print',
                'parent_id' => $ekanbanShikakeMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'ekanban_shikake_print_preview',
                'name' => 'Print Preview from Office',
                'url' => '/schedule/ekanban-shikake/print-preview',
                'icon' => 'fas fa-eye',
                'parent_id' => $ekanbanShikakeMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($shikakeSubmenus as $submenu) {
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
