<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CompteBloquerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_schedules_a_future_block_and_keeps_status_active()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $compte = Compte::factory()->for($admin, 'client')->create();

        $debut = Carbon::now()->addDays(2)->toDateTimeString();
        $fin = Carbon::now()->addDays(9)->toDateTimeString();

        $response = $this->actingAs($admin)
            ->postJson("/senghorfallou/v1/comptes/{$compte->id}/bloquer", [
                'date_debut_blocage' => $debut,
                'date_fin_blocage' => $fin,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Blocage programmé (statut restera actif jusqu\'à la date_debut_blocage)']);

        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'date_debut_blocage' => $debut,
            'date_fin_blocage' => $fin,
            'statut' => 'actif', // should remain actif until date arrives
        ]);
    }

    /** @test */
    public function it_blocks_immediately_when_date_debut_is_now_or_past()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $compte = Compte::factory()->for($admin, 'client')->create();

        $debut = Carbon::now()->toDateTimeString();
        $fin = Carbon::now()->addDays(7)->toDateTimeString();

        $response = $this->actingAs($admin)
            ->postJson("/senghorfallou/v1/comptes/{$compte->id}/bloquer", [
                'date_debut_blocage' => $debut,
                'date_fin_blocage' => $fin,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Compte bloqué avec succès']);

        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'statut' => 'bloque',
        ]);
    }
}
