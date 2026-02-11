<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMenuIcons extends Command
{
    protected $signature = 'menu:update-icons';
    protected $description = 'Update menu icons from Font Awesome 5 to Font Awesome 6 format';

    public function handle()
    {
        // Icon mapping from FA5 to FA6
        $iconMappings = [
            // Class prefix changes
            'fas fa-' => 'fa-solid fa-',
            'far fa-' => 'fa-regular fa-',
            'fab fa-' => 'fa-brands fa-',
        ];

        // Icon name changes from FA5 to FA6
        $iconNameMappings = [
            'fa-tachometer-alt' => 'fa-gauge-high',
            'fa-list-alt' => 'fa-rectangle-list',
            'fa-user-edit' => 'fa-user-pen',
            'fa-user-cog' => 'fa-user-gear',
            'fa-cog' => 'fa-gear',
            'fa-cogs' => 'fa-gears',
            'fa-edit' => 'fa-pen-to-square',
            'fa-money-check-alt' => 'fa-money-check-dollar',
            'fa-save' => 'fa-floppy-disk',
            'fa-times' => 'fa-xmark',
            'fa-window-close' => 'fa-rectangle-xmark',
            'fa-check-circle' => 'fa-circle-check',
            'fa-times-circle' => 'fa-circle-xmark',
            'fa-exclamation-circle' => 'fa-circle-exclamation',
            'fa-info-circle' => 'fa-circle-info',
            'fa-question-circle' => 'fa-circle-question',
            'fa-plus-circle' => 'fa-circle-plus',
            'fa-minus-circle' => 'fa-circle-minus',
            'fa-arrow-circle-left' => 'fa-circle-arrow-left',
            'fa-arrow-circle-right' => 'fa-circle-arrow-right',
            'fa-arrow-circle-up' => 'fa-circle-arrow-up',
            'fa-arrow-circle-down' => 'fa-circle-arrow-down',
            'fa-calendar-alt' => 'fa-calendar-days',
            'fa-file-alt' => 'fa-file-lines',
            'fa-external-link-alt' => 'fa-arrow-up-right-from-square',
            'fa-exchange-alt' => 'fa-right-left',
            'fa-sign-in-alt' => 'fa-right-to-bracket',
            'fa-sign-out-alt' => 'fa-right-from-bracket',
            'fa-sync-alt' => 'fa-arrows-rotate',
            'fa-sync' => 'fa-arrows-rotate',
            'fa-trash-alt' => 'fa-trash-can',
            'fa-tasks' => 'fa-list-check',
            'fa-ellipsis-h' => 'fa-ellipsis',
            'fa-ellipsis-v' => 'fa-ellipsis-vertical',
            'fa-search' => 'fa-magnifying-glass',
            'fa-comment-alt' => 'fa-message',
            'fa-sliders-h' => 'fa-sliders',
            'fa-random' => 'fa-shuffle',
            'fa-redo' => 'fa-arrow-rotate-right',
            'fa-undo' => 'fa-arrow-rotate-left',
            'fa-th' => 'fa-table-cells',
            'fa-th-large' => 'fa-table-cells-large',
            'fa-th-list' => 'fa-table-list',
            'fa-map-marked-alt' => 'fa-map-location-dot',
            'fa-users-cog' => 'fa-users-gear',
            'fa-project-diagram' => 'fa-diagram-project',
            'fa-phone-alt' => 'fa-phone-flip',
        ];

        $menus = DB::table('menus')->get();
        $updated = 0;

        foreach ($menus as $menu) {
            $originalIcon = $menu->icon;
            $newIcon = $originalIcon;

            // First, update class prefix
            foreach ($iconMappings as $old => $new) {
                if (str_starts_with($newIcon, $old)) {
                    $newIcon = str_replace($old, $new, $newIcon);
                    break;
                }
            }

            // Then, update icon names
            foreach ($iconNameMappings as $old => $new) {
                if (str_contains($newIcon, $old)) {
                    $newIcon = str_replace($old, $new, $newIcon);
                    break;
                }
            }

            if ($originalIcon !== $newIcon) {
                DB::table('menus')->where('id', $menu->id)->update(['icon' => $newIcon]);
                $this->info("Updated: {$menu->name} | {$originalIcon} => {$newIcon}");
                $updated++;
            }
        }

        $this->info("Total updated: {$updated} menus");
        return Command::SUCCESS;
    }
}
