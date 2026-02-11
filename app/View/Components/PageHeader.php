<?php

namespace App\View\Components;

use App\Models\Menu;
use Illuminate\View\Component;

class PageHeader extends Component
{
    public string $title;
    public array $breadcrumbs;

    /**
     * Create a new component instance.
     */
    public function __construct(?string $menuCode = null, ?string $title = null)
    {
        $this->breadcrumbs = [
            ['name' => 'Home', 'url' => route('dashboard')]
        ];

        if ($menuCode) {
            $menu = Menu::where('code', $menuCode)->first();
            
            if ($menu) {
                $this->title = $title ?? $menu->name;
                $this->buildBreadcrumbs($menu);
            } else {
                $this->title = $title ?? 'Page';
            }
        } else {
            $this->title = $title ?? 'Page';
        }
    }

    /**
     * Build breadcrumbs from menu hierarchy
     */
    protected function buildBreadcrumbs(Menu $menu): void
    {
        $crumbs = [];
        $current = $menu;

        // Build the path from current to root
        while ($current) {
            $crumbs[] = [
                'name' => $current->name,
                'url' => $current->url !== '#' ? url($current->url) : null,
                'is_current' => $current->id === $menu->id
            ];
            $current = $current->parent;
        }

        // Reverse to get root -> current order
        $crumbs = array_reverse($crumbs);

        // Add to breadcrumbs after Home
        foreach ($crumbs as $crumb) {
            $this->breadcrumbs[] = $crumb;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.page-header');
    }
}
