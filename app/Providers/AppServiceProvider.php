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
        // Menu sidebar - HANYA untuk view sidebar. Dulu memakai composer('*'):
        // 3 query menu dijalankan untuk SETIAP view yang dirender, termasuk partial
        // tombol aksi per baris tabel print (343 baris = 1.029 query, ±1 detik per
        // muat tabel) dan setiap tiket di preview print. Dengan ±50 PC di menu print,
        // itu ikut membuat CPU server penuh.
        View::composer('layouts.sidebar', function ($view) {
            $view->with('userMenus', $this->sidebarMenus());
        });
    }

    /** Menu yang boleh dibaca grup user yang sedang login. */
    private function sidebarMenus()
    {
        if (!auth()->check()) {
            return [];
        }

        // Get menus with read access
        $accessibleMenuIds = GroupMenuAccess::where('group_id', auth()->user()->group_id)
            ->where('can_read', true)
            ->pluck('menu_id')
            ->toArray();

        // Get parent menus
        return Menu::with([
                'children' => function ($query) use ($accessibleMenuIds) {
                    $query->where('is_active', 1)
                          ->whereIn('id', $accessibleMenuIds)
                          ->orderBy('order');
                },
                // Sub-sub-menu dibaca sidebar per sub-menu; tanpa eager load = 1 query
                // per sub-menu (±11 query per halaman). Isinya sama dengan lazy load.
                'children.children',
            ])
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->whereIn('id', $accessibleMenuIds)
            ->orderBy('order')
            ->get();
    }
}
