<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearApiCacheCommand extends Command
{
    protected $signature = 'api:clear-cache';

    protected $description = 'Clear route, config, and application caches for API deployments';

    public function handle(): int
    {
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        $this->info('Route, config, and application caches cleared.');

        return self::SUCCESS;
    }
}
