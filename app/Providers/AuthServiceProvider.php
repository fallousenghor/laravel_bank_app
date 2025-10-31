<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // If Passport is installed, register its routes and define common scopes.
        if (class_exists(Passport::class)) {
            // Register the routes necessary for issuing access tokens, revocation, clients...
            // Newer/alternative Passport versions may not expose a static `routes` method,
            // so check for existence before invoking to avoid fatal errors on startup.
            if (method_exists(Passport::class, 'routes')) {
                Passport::routes();
            }

            // Define some example scopes used across the application.
            if (method_exists(Passport::class, 'tokensCan')) {
                Passport::tokensCan([
                    'read' => 'Read resources',
                    'write' => 'Write resources',
                    'admin' => 'Admin privileges',
                ]);
            }

            // Optionally use JWT access tokens (if configured)
            if (method_exists(Passport::class, 'useJwtAccessTokens')) {
                Passport::useJwtAccessTokens();
            }

            // Set default token expiration windows (example values)
            if (method_exists(Passport::class, 'tokensExpireIn')) {
                Passport::tokensExpireIn(Carbon::now()->addMinutes(60));
            }
            if (method_exists(Passport::class, 'refreshTokensExpireIn')) {
                Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
            }

            // Always load keys from storage (more reliable for production)
            Passport::loadKeysFrom(storage_path());
        }
    }
}
