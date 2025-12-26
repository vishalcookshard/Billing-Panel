<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateLocked extends Command
{
    protected $signature = 'migrate:locked {--force : Force the operation to run when in production}';

    protected $description = 'Run migrations while holding a DB advisory lock to prevent concurrent runs';

    public function handle()
    {
        $key = 'migrations_lock';
        $lockAcquired = false;

        try {
            $this->info('Acquiring DB lock for migrations...');
            // MySQL GET_LOCK
            $res = DB::select("SELECT GET_LOCK(?, 10) as g", [$key]);
            if (!empty($res) && ($res[0]->g ?? $res[0]->G ?? false)) {
                $lockAcquired = true;
            }

            if (!$lockAcquired) {
                $this->error('Could not acquire migration lock. Another migration may be running.');
                return 1;
            }

            $this->info('Lock acquired. Running migrations...');
            Artisan::call('migrate', ['--force' => $this->option('force')]);
            $this->info(Artisan::output());

            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        } finally {
            if ($lockAcquired) {
                DB::select("SELECT RELEASE_LOCK(?)", [$key]);
                $this->info('Migration lock released.');
            }
        }
    }
}
