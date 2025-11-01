<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_tokens_and_cookies()
    {
        // Create a user
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password')]);

        // Ensure minimal oauth_clients rows exist (avoid seeder complexity inside test transaction)
        $now = now();
        \DB::table('oauth_clients')->insert([
            [
                'name' => 'Password Grant Client',
                'secret' => bin2hex(random_bytes(40)),
                'redirect' => url('/'),
                'personal_access_client' => false,
                'password_client' => true,
                'revoked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Personal Access Client',
                'secret' => bin2hex(random_bytes(40)),
                'redirect' => url('/'),
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data' => ['access_token', 'refresh_token', 'token_type', 'expires_in']]);
        $this->assertNotNull($response->json('data.access_token'));
        // Cookie assertions
        $this->assertStringContainsString('access_token', $response->headers->get('set-cookie'));
    }
}
