<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $code;

    /**
     * Create a new message instance.
     */
    public function __construct($user, string $password, string $code)
    {
        $this->user = $user;
        $this->password = $password;
        $this->code = $code;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Création de votre compte bancaire')
                    ->view('emails.client_created')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                        'code' => $this->code,
                    ]);
    }
}
