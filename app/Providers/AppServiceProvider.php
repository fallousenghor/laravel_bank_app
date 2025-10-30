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
use Laravel\Passport\Passport;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\Storage;

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

        // Enable the password grant when using Passport v12+ where it's disabled by default.
        // Guard with method_exists to remain compatible with older Passport versions.
        if (class_exists(Passport::class) && method_exists(Passport::class, 'enablePasswordGrant')) {
            try {
                Passport::enablePasswordGrant();
            } catch (\Throwable $e) {
                // non-fatal: log and continue
                \Log::warning('Failed to enable Passport password grant: ' . $e->getMessage());
            }
        }

        // Ensure a password grant client exists so the password grant flow works even
        // if environment variables are not configured. We create one programmatically
        // and allow the controller fallback to read it from the DB.
        try {
            $hasPasswordClient = \DB::table('oauth_clients')->where('password_client', 1)->exists();
            if (! $hasPasswordClient && class_exists(ClientRepository::class)) {
                $repo = new ClientRepository();
                // createPasswordGrantClient returns an array [client, plainTextSecret] in some versions,
                // but to remain compatible we'll handle both possibilities.
                $result = $repo->createPasswordGrantClient(null, 'Default Password Grant Client', config('app.url', 'http://localhost'));

                // The result may be a Client model or an array [Client, secret]
                $client = null;
                $secret = null;
                if (is_array($result)) {
                    $client = $result[0] ?? null;
                    $secret = $result[1] ?? null;
                } else {
                    $client = $result;
                }

                // If no secret was returned, attempt to read the secret from DB (may be unhashed)
                if (! $secret && $client && isset($client->id)) {
                    $db = \DB::table('oauth_clients')->where('id', $client->id)->first();
                    if ($db && isset($db->secret)) {
                        $secret = $db->secret;
                    }
                }

                // Persist non-sensitive indicator so ops know we created a client — do NOT log secrets.
                \Log::info('Created fallback Passport password client', ['client_id' => $client->id ?? null]);

                // Optionally write the client id (not secret) to storage for admin reference
                try {
                    $info = ['client_id' => $client->id ?? null, 'created_at' => now()->toDateTimeString()];
                    Storage::disk('local')->put('passport_fallback_client.json', json_encode($info));
                } catch (\Throwable $e) {
                    // ignore storage errors
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to ensure fallback Passport password client: ' . $e->getMessage());
        }
    }
}
