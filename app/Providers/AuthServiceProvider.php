<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use App\Models\User;
use App\Models\Invoice;
use App\Policies\UserPolicy;
use App\Policies\InvoicePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::define('access-admin', function ($user) {
            return ($user->is_admin ?? false) || ($user->role ?? '') === 'admin';
        });

        Gate::define('manage-settings', function ($user) {
            return ($user->is_admin ?? false) || ($user->role ?? '') === 'admin';
        });

        Gate::define('manage-plugins', function ($user) {
            return ($user->is_admin ?? false) || ($user->role ?? '') === 'admin';
        });
    }
}
