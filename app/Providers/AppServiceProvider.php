<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\LeadPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $email = (string) $request->input('email', '');
            $ipAddress = (string) $request->ip();

            return Limit::perMinute(5)->by(strtolower($email).'|'.$ipAddress);
        });

        RateLimiter::for('webhook', function (Request $request): Limit {
            $clientKey = (string) $request->header('X-Client-Key', 'unknown');

            return Limit::perMinute(60)->by($clientKey);
        });

        RateLimiter::for('api.crm', function (Request $request): Limit {
            $userKey = $request->user()?->getAuthIdentifier() ?? (string) $request->ip();

            return Limit::perMinute(120)->by((string) $userKey);
        });
    }
}
