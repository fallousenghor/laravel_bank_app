<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Interfaces\CompteRepositoryInterface;
use App\Repositories\CompteRepository;
use App\Interfaces\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CompteRepositoryInterface::class, CompteRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS scheme for generated absolute URLs when in production or when explicitly enabled
        // This helps avoid mixed content errors for assets generated with absolute URLs (e.g. l5-swagger)
        try {
            $forceHttps = Config::get('app.env') === 'production' || env('L5_SWAGGER_USE_HTTPS', false) || str_starts_with(Config::get('app.url', ''), 'https://');
            if ($forceHttps) {
                URL::forceScheme('https');
            }
        } catch (\Throwable $e) {
            // don't break the application boot if config isn't available
        }
    }
}
