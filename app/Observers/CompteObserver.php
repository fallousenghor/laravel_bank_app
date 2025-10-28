<?php

namespace App\Observers;

use App\Models\Compte;
use App\Events\CompteCreated;

class CompteObserver
{
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        // Déclencher l'événement de création de compte
        event(new CompteCreated($compte));
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        // Log important status changes
        if ($compte->wasChanged('statut')) {
            \Log::info('Statut du compte modifié', [
                'compte_id' => $compte->id,
                'numero' => $compte->numero,
                'ancien_statut' => $compte->getOriginal('statut'),
                'nouveau_statut' => $compte->statut
            ]);
        }
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        \Log::info('Compte supprimé (soft delete)', [
            'compte_id' => $compte->id,
            'numero' => $compte->numero,
            'statut' => $compte->statut
        ]);
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        \Log::info('Compte restauré', [
            'compte_id' => $compte->id,
            'numero' => $compte->numero
        ]);
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        \Log::warning('Compte supprimé définitivement', [
            'compte_id' => $compte->id,
            'numero' => $compte->numero
        ]);
    }
}
