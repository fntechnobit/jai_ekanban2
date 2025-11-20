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
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
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
        ];

        foreach ($submenus as $submenu) {
            Menu::create($submenu);
        }

        // Grant all permissions to Super Admin (group_id = 1)
        $allMenus = Menu::all();
        foreach ($allMenus as $menu) {
            GroupMenuAccess::create([
                'group_id' => 1,
                'menu_id' => $menu->id,
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);
        }
    }
}
