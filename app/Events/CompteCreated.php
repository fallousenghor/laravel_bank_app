<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public $compte;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }
}
