<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendCompteNotification
{
    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;
        \Log::info('CompteCreated event received', ['compte_id' => $compte->id]);

        // Récupérer l'utilisateur associé au compte et envoyer l'email
        $user = User::find($compte->utilisateur_id);
        \Log::info('User found', ['user_id' => $user?->id, 'email' => $user?->email]);

        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new CompteCreatedMail($compte));
                \Log::info('Email sent successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to send email', ['error' => $e->getMessage()]);
            }
        } else {
            \Log::warning('Cannot send email - no valid user or email found');
        }
    }
}
