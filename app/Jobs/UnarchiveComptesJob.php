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
            // Find comptes in archive database where date_fin_blocage has expired
            $comptesToUnarchive = DB::connection('archive')
                ->table('comptes')
                ->where('statut', 'bloque')
                ->whereNotNull('date_fin_blocage')
                ->where('date_fin_blocage', '<=', now())
                ->get();

            $unarchivedCount = 0;

            foreach ($comptesToUnarchive as $compteData) {
                DB::transaction(function () use ($compteData, &$unarchivedCount) {
                    // Get transactions from archive
                    $transactions = DB::connection('archive')
                        ->table('transactions')
                        ->where('compte_id', $compteData->id)
                        ->get();

                    // Move compte back to main database
                    $compteData->statut = 'actif';
                    $compteData->date_debut_blocage = null;
                    $compteData->date_fin_blocage = null;
                    unset($compteData->deleted_at); // Remove soft delete timestamp if present

                    DB::table('comptes')->insert((array) $compteData);

                    // Move transactions back to main database
                    if ($transactions->isNotEmpty()) {
                        foreach ($transactions as $transaction) {
                            unset($transaction->deleted_at); // Remove soft delete timestamp if present
                            DB::table('transactions')->insert((array) $transaction);
                        }
                    }

                    // Remove from archive database
                    DB::connection('archive')->table('comptes')->where('id', $compteData->id)->delete();
                    DB::connection('archive')->table('transactions')->where('compte_id', $compteData->id)->delete();

                    $unarchivedCount++;
                    Log::info("Unarchived compte {$compteData->numero} and its transactions");
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
