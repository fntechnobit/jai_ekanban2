<?php

return [
    'main_menu' => [
        [
            'title' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'gauge-high',
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
                    'icon' => 'gear',
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
                    'icon' => 'file-lines',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Inventory Report',
                    'url' => 'reports/inventory',
                    'icon' => 'file-lines',
                    'sub_menu' => '#'
                ],
            ]
        ],
        [
            'title' => 'System',
            'url' => '#',
            'icon' => 'gear',
            'sub_menu' => [
                [
                    'title' => 'Users',
                    'url' => 'system/users',
                    'icon' => 'users',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'User Groups',
                    'url' => 'system/user-groups',
                    'icon' => 'users-gear',
                    'sub_menu' => '#'
                ],
                [
                    'title' => 'Menus',
                    'url' => 'system/menus',
                    'icon' => 'bars',
                    'sub_menu' => '#'
                ],
            ]
        ],
    ],
];
