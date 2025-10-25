<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'role' => $this->role,
            'comptes' => $this->whenLoaded('comptes', function () {
                return $this->comptes->map(function ($compte) {
                    return [
                        'id' => $compte->id,
                        'numero' => $compte->numero
                    ];
                });
            })
        ];
    }
}
