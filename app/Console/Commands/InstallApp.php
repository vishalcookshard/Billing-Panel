<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminSetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class InstallApp extends Command
{
    protected $signature = 'app:install {--email=} {--password=}';

    protected $description = 'Run initial installation (migrate, seed, create admin user)';

    public function handle()
    {
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);

        $email = $this->option('email') ?: $this->ask('Admin email');
        $password = $this->option('password') ?: $this->secret('Admin password');

        $user = User::firstOrCreate(['email' => $email], ['name' => 'Admin', 'password' => bcrypt($password), 'is_admin' => true]);

        // Create basic RBAC scaffolding and seed standard permissions
        $this->call('db:seed', ['--class' => \Database\Seeders\RbacSeeder::class]);

        // Attach admin role to the created admin user
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $this->info('Admin user created and granted admin role: ' . $user->email);

        $this->info('Installation complete.');
    }
}
