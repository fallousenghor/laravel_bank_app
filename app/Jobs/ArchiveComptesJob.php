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

class ArchiveComptesJob implements ShouldQueue
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
        Log::info('Starting ArchiveComptesJob');

        try {
            // Find comptes where statut is 'bloque' and date_debut_blocage has expired
            $comptesToArchive = Compte::where('statut', 'bloque')
                ->whereNotNull('date_debut_blocage')
                ->where('date_debut_blocage', '<=', now())
                ->with('transactions')
                ->get();

            $archivedCount = 0;

            foreach ($comptesToArchive as $compte) {
                DB::transaction(function () use ($compte, &$archivedCount) {
                    // Get all transactions for this compte
                    $transactions = $compte->transactions;

                    // Prepare compte data for archive
                    $compteData = $compte->toArray();
                    $compteData['deleted_at'] = now(); // Mark as soft deleted in archive

                    // Insert compte into archive database
                    DB::connection('archive')->table('comptes')->insert($compteData);

                    // Insert transactions into archive database
                    if ($transactions->isNotEmpty()) {
                        $transactionData = $transactions->map(function ($transaction) {
                            $data = $transaction->toArray();
                            $data['deleted_at'] = now(); // Mark as soft deleted in archive
                            return $data;
                        })->toArray();

                        DB::connection('archive')->table('transactions')->insert($transactionData);
                    }

                    // Soft delete from main database
                    $compte->delete();

                    // Also delete transactions from main database
                    $compte->transactions()->delete();

                    $archivedCount++;
                    Log::info("Archived compte {$compte->numero} and its transactions");
                });
            }

            Log::info("ArchiveComptesJob completed. Archived {$archivedCount} comptes");

        } catch (\Exception $e) {
            Log::error('Error in ArchiveComptesJob: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
