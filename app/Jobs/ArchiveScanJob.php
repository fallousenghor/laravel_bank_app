<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting ArchiveScanJob');

        try {
            // First, mark as "bloque" any compte whose date_debut_blocage has arrived
            $updated = DB::table('comptes')
                ->whereNotNull('date_debut_blocage')
                ->where('date_debut_blocage', '<=', now())
                ->where('statut', '!=', 'bloque')
                ->update(['statut' => 'bloque', 'updated_at' => now()]);

            if ($updated) {
                Log::info("ArchiveScanJob: marked {$updated} comptes as 'bloque' because date_debut_blocage has arrived");
            }

            $comptes = DB::table('comptes')
                ->where('statut', 'bloque')
                ->whereNotNull('date_debut_blocage')
                ->where('date_debut_blocage', '<=', now())
                ->get();

            // Verify target schema exists before attempting per-compte work
            $railwaySchema = DB::connection('railway')->getSchemaBuilder();
            if (! $railwaySchema->hasTable('archive_comptes') || ! $railwaySchema->hasTable('archive_transactions')) {
                Log::error('ArchiveScanJob aborted: required archive tables do not exist on railway connection');
                return;
            }

            $archived = 0;

            foreach ($comptes as $compte) {
                try {
                    // Prepare compte data
                    $compteArr = (array) $compte;

                    // Prepare transactions with explicit columns to avoid insert errors
                    $transactions = DB::table('transactions')->where('compte_id', $compte->id)->get();
                    $txArr = [];
                    if ($transactions->isNotEmpty()) {
                        $txArr = $transactions->map(function ($t) {
                            return [
                                'id' => $t->id,
                                'montant' => $t->montant,
                                'type' => $t->type,
                                'date' => $t->date,
                                'compte_id' => $t->compte_id,
                                'created_at' => $t->created_at,
                                'updated_at' => $t->updated_at,
                            ];
                        })->toArray();
                    }

                    // Insert into railway inside a railway transaction; only on success we delete local rows
                    DB::connection('railway')->transaction(function () use ($compteArr, $txArr) {
                        // Insert compte (if exists, let it throw so we can catch and handle)
                        DB::connection('railway')->table('archive_comptes')->insert($compteArr);

                        if (! empty($txArr)) {
                            DB::connection('railway')->table('archive_transactions')->insert($txArr);
                        }
                    });

                    // If we reach here, inserts on railway succeeded: delete local copies
                    DB::table('transactions')->where('compte_id', $compte->id)->delete();
                    DB::table('comptes')->where('id', $compte->id)->delete();

                    $archived++;
                    Log::info("Archived compte {$compte->numero} to railway");
                } catch (\Exception $e) {
                    Log::error('ArchiveScanJob per-compte error: '.$e->getMessage(), ['compte' => $compte->id]);

                    // Attempt to cleanup partial inserts on railway if possible
                    try {
                        if (DB::connection('railway')->getSchemaBuilder()->hasTable('archive_transactions')) {
                            DB::connection('railway')->table('archive_transactions')->where('compte_id', $compte->id)->delete();
                        }
                        if (DB::connection('railway')->getSchemaBuilder()->hasTable('archive_comptes')) {
                            DB::connection('railway')->table('archive_comptes')->where('id', $compte->id)->delete();
                        }
                    } catch (\Throwable $t) {
                        Log::error('Failed to rollback railway inserts: '.$t->getMessage());
                    }
                }
            }

            Log::info("ArchiveScanJob completed. Archived {$archived} comptes.");

        } catch (\Exception $e) {
            Log::error('ArchiveScanJob failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}
