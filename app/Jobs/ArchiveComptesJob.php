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
use Illuminate\Support\Facades\Schema;

class ArchiveComptesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $compteId;
    /**
     * Force this job to use the database queue connection so it won't execute
     * synchronously when the app's default driver is `sync`.
     *
     * Make sure you have a queue worker running (php artisan queue:work --connection=database)
     */


    /**
     * Create a new job instance.
     */
    public function __construct($compteId)
    {
        $this->compteId = $compteId;
        // Prefer not to redeclare $connection/$queue properties (they exist in Queueable trait)
        // Assign target connection/queue at runtime so jobs won't execute synchronously when default driver is 'sync'
        $this->connection = 'database';
        $this->queue = 'default';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting ArchiveComptesJob for compte ' . $this->compteId);

        try {
            $compte = Compte::withTrashed()->findOrFail($this->compteId);

            if ($compte->statut !== 'ferme') {
                Log::info("Skipping archive for compte {$compte->numero} as it's not marked as closed");
                return;
            }

            try {
                // Get all data outside the transaction first
                $transactions = Transaction::where('compte_id', $compte->id)->get();

                // Calculate the final balance
                $solde = 0;
                foreach ($transactions as $transaction) {
                    $solde += $transaction->type === 'depot' ? $transaction->montant : -$transaction->montant;
                }

                // Prepare compte data with fixed values
                $compteDataFull = [
                    'id' => $compte->id,
                    'numero' => $compte->numero,
                    'type' => $compte->type,
                    'solde' => $solde,
                    'statut' => 'ferme',
                    'client_id' => $compte->client_id,
                    'devise' => $compte->devise,
                    'date_creation' => $compte->date_creation,
                    'date_fermeture' => now(),
                    'created_at' => $compte->created_at,
                    'updated_at' => now(),
                ];

                // Only include columns that exist on the archive connection to avoid SQL errors
                $archiveConnection = DB::connection('archive');
                $archiveCompteData = [];
                foreach ($compteDataFull as $col => $val) {
                    try {
                        if (Schema::connection('archive')->hasColumn('comptes', $col)) {
                            $archiveCompteData[$col] = $val;
                        }
                    } catch (\Exception $e) {
                        // If the archive connection/schema is not available or throws, skip that column
                        Log::warning("Archive schema check failed for column {$col}: " . $e->getMessage());
                    }
                }

                // Perform archive operations on archive connection separately. Any failures there should not abort
                // the main database transaction used for deleting data from the primary DB.
                try {
                    if (!empty($archiveCompteData)) {
                        // Upsert compte on archive connection
                        if ($archiveConnection->table('comptes')->where('id', $archiveCompteData['id'])->exists()) {
                            $archiveConnection->table('comptes')->where('id', $archiveCompteData['id'])->update($archiveCompteData);
                            Log::info("Updated existing archived compte {$archiveCompteData['id']}");
                        } else {
                            $archiveConnection->table('comptes')->insert($archiveCompteData);
                            Log::info("Inserted archived compte {$archiveCompteData['id']}");
                        }
                    } else {
                        Log::warning("No matching columns found on archive.comptes for compte {$compte->id}, skipping insert/update.");
                    }

                    if ($transactions->isNotEmpty()) {
                        foreach ($transactions as $transaction) {
                            $txData = [
                                'id' => $transaction->id,
                                'type' => $transaction->type,
                                'montant' => $transaction->montant,
                                'compte_id' => $transaction->compte_id,
                                'created_at' => $transaction->created_at,
                                'updated_at' => $transaction->updated_at,
                            ];

                            try {
                                $archiveConnection->table('transactions')->updateOrInsert(['id' => $txData['id']], $txData);
                            } catch (\Exception $e) {
                                Log::warning("Failed to upsert transaction {$txData['id']} into archive: " . $e->getMessage());
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Archive errors should not prevent deleting data from the primary DB. Log and continue.
                    Log::error("Archive operation failed for compte {$compte->id}: " . $e->getMessage());
                }

                // Now delete from main database inside its own transaction to ensure consistency there
                DB::transaction(function () use ($compte) {
                    Transaction::where('compte_id', $compte->id)->delete();
                    $compte->forceDelete();
                });

                Log::info("Successfully archived compte {$compte->numero} and its transactions (archive ops may have partially failed)");
            } catch (\Exception $e) {
                Log::error("Error while preparing archive data: " . $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error in ArchiveComptesJob: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
