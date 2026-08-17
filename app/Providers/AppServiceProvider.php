<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Menu;
use App\Models\GroupMenuAccess;
use App\Services\Listing\ApiListingSource;
use App\Services\Listing\DbListingSource;
use App\Services\Listing\ListingSourceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sumber data listing ditentukan konfigurasi, bukan kode.
        // 'api' = REST API SIREP (mode utama jai_ekanban2)
        // 'db'  = database SIREP lama (jalur cadangan, perilaku jai_ekanban)
        $this->app->bind(ListingSourceInterface::class, function ($app) {
            return config('sirep.listing_source') === 'db'
                ? $app->make(DbListingSource::class)
                : $app->make(ApiListingSource::class);
        });
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
