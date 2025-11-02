<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnarchiveScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Starting UnarchiveScanJob');

        try {
            $archives = DB::connection('railway')
                ->table('archive_comptes')
                ->where('statut', 'bloque')
                ->whereNotNull('date_fin_blocage')
                ->where('date_fin_blocage', '<=', now())
                ->get();

            $restored = 0;

            foreach ($archives as $row) {
                try {
                    $compteArr = (array) $row;

                    // Prepare compte for insertion back to main DB
                    $compteArr['statut'] = 'actif';
                    $compteArr['date_debut_blocage'] = null;
                    $compteArr['date_fin_blocage'] = null;

                    DB::table('comptes')->insert($compteArr);

                    // Move transactions back
                    $txs = DB::connection('railway')->table('archive_transactions')->where('compte_id', $row->id)->get();
                    if ($txs->isNotEmpty()) {
                        $txArr = $txs->map(function ($t) { return (array) $t; })->toArray();
                        DB::table('transactions')->insert($txArr);
                    }

                    // Delete from railway archive
                    DB::connection('railway')->table('archive_transactions')->where('compte_id', $row->id)->delete();
                    DB::connection('railway')->table('archive_comptes')->where('id', $row->id)->delete();

                    $restored++;
                    Log::info("Unarchived compte {$row->numero} from railway");
                } catch (\Exception $e) {
                    Log::error('UnarchiveScanJob per-row error: '.$e->getMessage(), ['id' => $row->id]);
                }
            }

            Log::info("UnarchiveScanJob completed. Restored {$restored} comptes.");

        } catch (\Exception $e) {
            Log::error('UnarchiveScanJob failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}
