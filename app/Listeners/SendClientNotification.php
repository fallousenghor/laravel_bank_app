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
        // Send email with authentication details using a Mailable when email is available
        $email = $event->user->email ?? null;

        if ($email) {
            try {
                Mail::to($email)->send(new ClientCreatedMail($event->user, $event->password, $event->code));
                Log::info("Email envoyé à {$email} pour la création du compte (user_id: {$event->user->id})");
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi de l'email à {$email}: " . $e->getMessage(), [
                    'user_id' => $event->user->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            // No email provided — log so admin can follow up or provide alternate channel (SMS)
            Log::info("Aucun email fourni pour l'utilisateur (id: {$event->user->id}). L'email de bienvenue n'a pas été envoyé.");
            // Optionally: implement SMS sending if telephone is available and a provider is configured.
        }

    }
}
