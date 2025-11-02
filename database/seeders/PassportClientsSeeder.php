<?php




namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\DB;

class PassportClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder will create a password grant client and a personal access client
     * only if they don't already exist. It uses Passport's ClientRepository so
     * secrets are generated correctly for the current Passport version.
     */
    public function run()
    {
        $repo = new ClientRepository();

        // Create password grant client if missing
        if (! DB::table('oauth_clients')->where('password_client', true)->exists()) {
            $client = $repo->createPasswordGrantClient(null, 'Password Grant Client', 'http://localhost');
            // Log to storage/logs/laravel.log so you can fetch secret after deploy
            logger()->info('Passport password client created', ['id' => $client->id, 'secret' => $client->secret]);
        } else {
            logger()->info('Passport password client already exists');
        }

        // Create personal access client if missing
        if (! DB::table('oauth_personal_access_clients')->exists()) {
            $client = $repo->createPersonalAccessClient(null, 'Personal Access Client', 'http://localhost');
            // ensure record exists in oauth_personal_access_clients table
            DB::table('oauth_personal_access_clients')->updateOrInsert([
                'client_id' => $client->id,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            logger()->info('Passport personal access client created', ['id' => $client->id, 'secret' => $client->secret]);
        } else {
            logger()->info('Passport personal access client already exists');
        }
    }
}

