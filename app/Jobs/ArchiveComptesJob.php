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
            // Find comptes where date_debut_blocage has expired and they are not yet archived
            $comptesToArchive = Compte::where('statut', 'bloque')
                ->whereNotNull('date_debut_blocage')
                ->where('date_debut_blocage', '<=', now())
                ->whereNull('deleted_at') // Not already soft deleted
                ->get();

            $archivedCount = 0;

            foreach ($comptesToArchive as $compte) {
                DB::transaction(function () use ($compte, &$archivedCount) {
                    // Soft delete the compte (archive it)
                    $compte->delete();

                    // Also archive all related transactions
                    Transaction::where('compte_id', $compte->id)->delete();

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
