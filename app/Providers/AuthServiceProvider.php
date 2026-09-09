<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\CleaningTask' => 'App\Policies\CleaningTaskPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Kost payment detail - anyone with reports.kost or view reports can see
        Gate::define('viewKostPaymentDetail', function ($user, $booking = null) {
            return $user->can('view reports') || $user->can('reports.kost');
        });

        // Kost payment approval - only manager/admin/pemimpin
        Gate::define('approveKostPayment', function ($user) {
            return $user->can('manage system') || $user->hasRole(['Admin', 'Manager', 'General Manager']);
        });
    }
}
