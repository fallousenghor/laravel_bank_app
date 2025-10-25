<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'montant' => $this->montant,
            'type' => $this->type,
            'date' => $this->date,
            'compte' => $this->whenLoaded('compte', function () {
                return [
                    'numero' => $this->compte->numero
                ];
            })
        ];
    }
}
