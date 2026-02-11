<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share menu data with all views
        View::composer('*', function ($view) {
            $menus = [];
            
            if (auth()->check()) {
                $user = auth()->user();
                $groupId = $user->group_id;
                
                // Get menus with read access
                $accessibleMenuIds = GroupMenuAccess::where('group_id', $groupId)
                    ->where('can_read', true)
                    ->pluck('menu_id')
                    ->toArray();
                
                // Get parent menus
                $menus = Menu::with(['children' => function($query) use ($accessibleMenuIds) {
                        $query->where('is_active', 1)
                              ->whereIn('id', $accessibleMenuIds)
                              ->orderBy('order');
                    }])
                    ->whereNull('parent_id')
                    ->where('is_active', 1)
                    ->whereIn('id', $accessibleMenuIds)
                    ->orderBy('order')
                    ->get();
            }
            
            $view->with('userMenus', $menus);
        });
    }
}
