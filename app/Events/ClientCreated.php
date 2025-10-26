<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, SerializesModels;

    public $user;
    public $password;
    public $code;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $password, string $code)
    {
        $this->user = $user;
        $this->password = $password;
        $this->code = $code;
    }
}
