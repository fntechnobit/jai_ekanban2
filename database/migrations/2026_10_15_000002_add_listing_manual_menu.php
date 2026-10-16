<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Menu;
use App\Models\GroupMenuAccess;

return new class extends Migration
{
    /**
     * Add Schedule > Listing Manual menu (after Verification).
     */
    public function up(): void
    {
        $scheduleMenu = Menu::where('code', 'schedule')->first();

        if (!$scheduleMenu || Menu::where('code', 'listing_manual')->exists()) {
            return;
        }

        $menu = Menu::create([
            'code'      => 'listing_manual',
            'name'      => 'Listing Manual',
            'url'       => '/schedule/listing-manual',
            'icon'      => 'fa-solid fa-keyboard',
            'parent_id' => $scheduleMenu->id,
            'order'     => (Menu::where('parent_id', $scheduleMenu->id)->max('order') ?? 0) + 1,
            'is_active' => true,
        ]);

        // Listing manual adalah bahan generate, jadi aksesnya mengikuti Assy Scheduler.
        // Tanpa baris group_menu_access menu ini tersembunyi.
        $schedulerMenu = Menu::where('code', 'assy_scheduler')->first();

        if ($schedulerMenu) {
            foreach (GroupMenuAccess::where('menu_id', $schedulerMenu->id)->get() as $access) {
                GroupMenuAccess::updateOrCreate(
                    ['group_id' => $access->group_id, 'menu_id' => $menu->id],
                    [
                        'can_create' => $access->can_create,
                        'can_read'   => $access->can_read,
                        'can_update' => $access->can_update,
                        'can_delete' => $access->can_delete,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $menu = Menu::where('code', 'listing_manual')->first();

        if ($menu) {
            GroupMenuAccess::where('menu_id', $menu->id)->delete();
            $menu->delete();
        }
    }
};
