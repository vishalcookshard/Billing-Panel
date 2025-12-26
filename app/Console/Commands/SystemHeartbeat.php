<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SystemHeartbeat extends Command
{
    protected $signature = 'system:heartbeat';

    protected $description = 'Write a heartbeat key to cache for monitoring (should be scheduled)';

    public function handle()
    {
        Cache::put('system:heartbeat', now()->toDateTimeString(), 300); // 5 minutes
        $this->info('heartbeat written');
        return 0;
    }
}
