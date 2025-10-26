<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use App\Mail\ClientCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

// Twilio client will be used conditionally if the SDK is installed and env is configured

class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ClientCreated $event): void
    {
        // Send email with authentication details using a Mailable
        try {
            Mail::to($event->user->email)->send(new ClientCreatedMail($event->user, $event->password, $event->code));
            Log::info("Email envoyé à {$event->user->email} pour la création du compte");
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de l'email à {$event->user->email}: " . $e->getMessage());
        }

    }
}
