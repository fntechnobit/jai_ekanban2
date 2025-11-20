<?php

return [
    'main_menu' => [
        [
            'title' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'tachometer-alt',
            'sub_menu' => '#'
        ],
        [
            'title' => 'Kanban Management',
            'url' => '#',
            'icon' => 'clipboard-list',
            'sub_menu' => [
                [
                    'title' => 'Kanban Cards',
                    'url' => 'kanban/cards',
                    'icon' => 'credit-card',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Suppliers',
                    'url' => 'kanban/suppliers',
                    'icon' => 'truck',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Parts',
                    'url' => 'kanban/parts',
                    'icon' => 'cog',
                    'sub_menu' => '#'
                ],
            ]
        ],
        [
            'title' => 'Reports',
            'url' => '#',
            'icon' => 'chart-bar',
            'sub_menu' => [
                [
                    'title' => 'Kanban Status',
                    'url' => 'reports/kanban-status',
                    'icon' => 'file-alt',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Inventory Report',
                    'url' => 'reports/inventory',
                    'icon' => 'file-alt',
                    'sub_menu' => '#'
                ],
            ]
        ],
        [
            'title' => 'Settings',
            'url' => '#',
            'icon' => 'cogs',
            'sub_menu' => [
                [
                    'title' => 'Users',
                    'url' => 'settings/users',
                    'icon' => 'users',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Roles',
                    'url' => 'settings/roles',
                    'icon' => 'user-shield',
                    'sub_menu' => '#'
                ],
            ]
        ],
    ],
];
