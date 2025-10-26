<?php

namespace Tests\Feature;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompteControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test validation for index method with invalid parameters.
     */
    public function test_index_validation_fails_with_invalid_parameters(): void
    {
        $response = $this->getJson('/senghorfallou/v1/comptes?page=abc&limit=150&type=invalid&statut=invalid&sort=invalid&order=invalid&admin_id=abc');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['page', 'limit', 'type', 'statut', 'sort', 'order', 'admin_id']);
    }

    /**
     * Test validation for index method with valid parameters.
     */
    public function test_index_validation_passes_with_valid_parameters(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user, 'api');

        $response = $this->getJson('/senghorfallou/v1/comptes?page=1&limit=10&type=epargne&statut=actif&sort=solde&order=desc&admin_id=1');

        $response->assertStatus(200);
    }

    /**
     * Test validation for show method with invalid UUID.
     */
    public function test_show_validation_fails_with_invalid_uuid(): void
    {
        $response = $this->getJson('/api/v1/comptes/invalid-uuid');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id']);
    }

    /**
     * Test validation for show method with non-existent UUID.
     */
    public function test_show_validation_fails_with_non_existent_uuid(): void
    {
        $response = $this->getJson('/api/v1/comptes/550e8400-e29b-41d4-a716-446655440000');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id']);
    }

    /**
     * Test validation for mine method with invalid user_id.
     */
    public function test_mine_validation_fails_with_invalid_user_id(): void
    {
        $response = $this->getJson('/api/v1/comptes/mine?user_id=abc');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test validation for mine method with non-existent user_id.
     */
    public function test_mine_validation_fails_with_non_existent_user_id(): void
    {
        $response = $this->getJson('/api/v1/comptes/mine?user_id=999');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test validation for mine method without user_id when not authenticated.
     */
    public function test_mine_requires_user_id_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/v1/comptes/mine');

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => "Paramètre 'user_id' requis lorsque non authentifié"
                 ]);
    }

    /**
     * Test successful account creation for new client.
     */
    public function test_store_creates_account_for_new_client(): void
    {
        $clientData = [
            'titulaire' => 'Amadou Diallo',
            'email' => 'amadou.diallo@example.com',
            'telephone' => '771234567',
            'adresse' => 'Dakar, Sénégal',
            'nci' => '1234567890123'
        ];

        $accountData = [
            'type' => 'cheque',
            'solde' => 50000,
            'devise' => 'FCFA',
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'numero',
                         'titulaire',
                         'type',
                         'solde',
                         'devise',
                         'statut',
                         'dateCreation',
                         'client' => [
                             'id',
                             'nom',
                             'prenom',
                             'email',
                             'telephone'
                         ]
                     ],
                     'message'
                 ]);

        // Verify client was created
        $this->assertDatabaseHas('users', [
            'email' => 'amadou.diallo@example.com',
            'telephone' => '771234567',
            'nci' => '1234567890123',
            'role' => 'client'
        ]);

        // Verify account was created
        $this->assertDatabaseHas('comptes', [
            'type' => 'Chèque',
            'solde' => 50000,
            'devise' => 'FCFA',
            'statut' => 'Actif'
        ]);
    }

    /**
     * Test account creation for existing client.
     */
    public function test_store_creates_account_for_existing_client(): void
    {
        // Create existing client
        $existingClient = User::factory()->create([
            'telephone' => '771234567',
            'email' => 'existing@example.com'
        ]);

        $clientData = [
            'titulaire' => 'Existing Client',
            'email' => 'existing@example.com',
            'telephone' => '771234567',
            'adresse' => 'Dakar, Sénégal'
        ];

        $accountData = [
            'type' => 'epargne',
            'solde' => 25000,
            'devise' => 'FCFA',
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(201);

        // Verify no new client was created
        $this->assertEquals(1, User::where('telephone', '771234567')->count());

        // Verify account was created for existing client
        $this->assertDatabaseHas('comptes', [
            'utilisateur_id' => $existingClient->id,
            'type' => 'Épargne',
            'solde' => 25000
        ]);
    }

    /**
     * Test validation fails with invalid telephone number.
     */
    public function test_store_validation_fails_with_invalid_telephone(): void
    {
        $clientData = [
            'titulaire' => 'Test User',
            'email' => 'test@example.com',
            'telephone' => 'invalid-phone',
            'adresse' => 'Test Address'
        ];

        $accountData = [
            'type' => 'cheque',
            'solde' => 50000,
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.telephone']);
    }

    /**
     * Test validation fails with invalid NCI.
     */
    public function test_store_validation_fails_with_invalid_nci(): void
    {
        $clientData = [
            'titulaire' => 'Test User',
            'email' => 'test@example.com',
            'telephone' => '771234567',
            'adresse' => 'Test Address',
            'nci' => 'invalid-nci'
        ];

        $accountData = [
            'type' => 'cheque',
            'solde' => 50000,
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.nci']);
    }

    /**
     * Test validation fails with insufficient balance.
     */
    public function test_store_validation_fails_with_insufficient_balance(): void
    {
        $clientData = [
            'titulaire' => 'Test User',
            'email' => 'test@example.com',
            'telephone' => '771234567',
            'adresse' => 'Test Address'
        ];

        $accountData = [
            'type' => 'cheque',
            'solde' => 5000, // Below minimum 10000
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['solde']);
    }

    /**
     * Test validation fails with duplicate email.
     */
    public function test_store_validation_fails_with_duplicate_email(): void
    {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $clientData = [
            'titulaire' => 'New User',
            'email' => 'existing@example.com', // Duplicate email
            'telephone' => '772345678',
            'adresse' => 'Test Address'
        ];

        $accountData = [
            'type' => 'cheque',
            'solde' => 50000,
            'client' => $clientData
        ];

        $response = $this->postJson('/api/v1/comptes', $accountData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.email']);
    }

    /**
     * Test account number generation is unique.
     */
    public function test_store_generates_unique_account_numbers(): void
    {
        $clientData1 = [
            'titulaire' => 'User One',
            'email' => 'user1@example.com',
            'telephone' => '771234567',
            'adresse' => 'Address 1'
        ];

        $clientData2 = [
            'titulaire' => 'User Two',
            'email' => 'user2@example.com',
            'telephone' => '772345678',
            'adresse' => 'Address 2'
        ];

        $accountData1 = [
            'type' => 'cheque',
            'solde' => 50000,
            'client' => $clientData1
        ];

        $accountData2 = [
            'type' => 'cheque',
            'solde' => 50000,
            'client' => $clientData2
        ];

        $response1 = $this->postJson('/api/v1/comptes', $accountData1);
        $response2 = $this->postJson('/api/v1/comptes', $accountData2);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $account1 = $response1->json('data');
        $account2 = $response2->json('data');

        $this->assertNotEquals($account1['numero'], $account2['numero']);
    }
}
