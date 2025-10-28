<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * ArchiveComptesJob stubbed out.
 *
 * The archiving background job was causing side-effects in some environments.
 * To ensure removing the job does not break any action, this class is now a
 * harmless no-op that only logs when invoked. Keep the class present so any
 * historical dispatches won't cause fatal errors, but no archiving will occur.
 */
class ArchiveComptesJob
{
    use Dispatchable;

    protected $compteId;

    public function __construct($compteId)
    {
        $this->compteId = $compteId;
    }

    public function handle()
    {
        Log::info("ArchiveComptesJob invoked but archiving is disabled for compte {$this->compteId}");
        // Intentionally do nothing else.
        return;
    }
}
