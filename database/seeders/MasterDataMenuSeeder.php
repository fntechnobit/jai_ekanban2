<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

class MasterDataMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Master Data parent menu
        $masterDataMenu = Menu::firstOrCreate(
            ['code' => 'master_data'],
            [
                'name' => 'Master Data',
                'url' => '#',
                'icon' => 'fa-solid fa-database',
                'parent_id' => null,
                'order' => 3,
                'is_active' => true,
            ]
        );

        // Create submenu for Master Data
        $submenus = [
            [
                'code' => 'master_area',
                'name' => 'Master Area',
                'url' => '/master-data/master-area',
                'icon' => 'fa-solid fa-map-location-dot',
                'parent_id' => $masterDataMenu->id,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'master_family',
                'name' => 'Master Family',
                'url' => '/master-data/master-family',
                'icon' => 'fa-solid fa-object-group',
                'parent_id' => $masterDataMenu->id,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'master_conveyor',
                'name' => 'Master Conveyor',
                'url' => '/master-data/master-conveyor',
                'icon' => 'fa-solid fa-dolly',
                'parent_id' => $masterDataMenu->id,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'master_machine',
                'name' => 'Master Machine',
                'url' => '/master-data/master-machine',
                'icon' => 'fa-solid fa-industry',
                'parent_id' => $masterDataMenu->id,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'master_shikake',
                'name' => 'Shikake Data',
                'url' => '/master-data/master-shikake',
                'icon' => 'fa-solid fa-table',
                'parent_id' => $masterDataMenu->id,
                'order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'master_circuit',
                'name' => 'Circuit Data',
                'url' => '/master-data/master-circuit',
                'icon' => 'fa-solid fa-diagram-project',
                'parent_id' => $masterDataMenu->id,
                'order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($submenus as $submenu) {
            $menu = Menu::firstOrCreate(
                ['code' => $submenu['code']],
                $submenu
            );

            // Grant all permissions to Super Admin (group_id = 1) for this menu
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

        // Also grant permissions to the parent menu
        GroupMenuAccess::firstOrCreate(
            [
                'group_id' => 1,
                'menu_id' => $masterDataMenu->id,
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
