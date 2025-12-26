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

        // Create basic RBAC scaffolding
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $manageSettings = \App\Models\Permission::firstOrCreate(['name' => 'manage-settings'], ['label' => 'Manage Settings']);
        $adminRole->permissions()->syncWithoutDetaching([$manageSettings->id]);
        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->info('Admin user created and granted admin role: ' . $user->email);

        $this->info('Installation complete.');
    }
}
