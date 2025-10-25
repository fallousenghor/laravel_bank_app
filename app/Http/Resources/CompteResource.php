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
            'numero' => $this->numero,
            'type' => $this->type,
            'solde' => $this->solde,
            'statut' => $this->statut,
            'date_creation' => $this->date_creation,
            'client' => [
                'id' => $this->utilisateur?->id,
                'prenom' => $this->utilisateur?->prenom,
                'nom' => $this->utilisateur?->nom,
                'email' => $this->utilisateur?->email,
            ],
        ];
    }
}
