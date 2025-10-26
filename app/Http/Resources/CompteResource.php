<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->utilisateur ? $this->utilisateur->prenom . ' ' . $this->utilisateur->nom : null,
            'type' => $this->type,
            'solde' => (float) $this->solde,
            'devise' => 'FCFA',
            'dateCreation' => $this->date_creation ? \Carbon\Carbon::parse($this->date_creation)->toISOString() : null,
            'statut' => $this->statut,
            'motifBlocage' => $this->statut === 'Bloqué' ? 'Inactivité de 30+ jours' : null,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1
            ]
        ];
    }
}
