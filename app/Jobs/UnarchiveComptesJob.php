<?php

namespace App\Jobs;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnarchiveComptesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting UnarchiveComptesJob');

        try {
            // Find comptes where date_fin_blocage has expired and they are archived (soft deleted)
            $comptesToUnarchive = Compte::onlyTrashed()
                ->where('statut', 'bloque')
                ->whereNotNull('date_fin_blocage')
                ->where('date_fin_blocage', '<=', now())
                ->get();

            $unarchivedCount = 0;

            foreach ($comptesToUnarchive as $compte) {
                DB::transaction(function () use ($compte, &$unarchivedCount) {
                    // Restore the compte (unarchive it)
                    $compte->restore();

                    // Also restore all related transactions
                    Transaction::onlyTrashed()
                        ->where('compte_id', $compte->id)
                        ->restore();

                    // Reset blocking dates and status
                    $compte->update([
                        'statut' => 'actif',
                        'date_debut_blocage' => null,
                        'date_fin_blocage' => null,
                    ]);

                    $unarchivedCount++;
                    Log::info("Unarchived compte {$compte->numero} and its transactions");
                });
            }

            Log::info("UnarchiveComptesJob completed. Unarchived {$unarchivedCount} comptes");

        } catch (\Exception $e) {
            Log::error('Error in UnarchiveComptesJob: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
