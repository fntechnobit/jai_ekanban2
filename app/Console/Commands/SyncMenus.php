<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncMenus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync menu structure from seeders without resetting database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing menus from seeders...');
        
        // Run only menu-related seeders
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MenuSeeder',
        ]);
        
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\MasterDataMenuSeeder',
        ]);
        
        $this->info('✓ Menu sync completed successfully!');
        $this->line('');
        $this->line('All menus and permissions have been synchronized.');
        
        return Command::SUCCESS;
    }
}
