<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

    // Archive job scheduling has been disabled to avoid background archiving side-effects.
    // If needed in the future re-enable the job by scheduling it here.
    // $schedule->job(new \App\Jobs\ArchiveComptesJob)->daily();

    // Schedule unarchiving job to run daily at midnight
    $schedule->job(new \App\Jobs\UnarchiveComptesJob)->daily();

    // Run archive scan every 5 minutes to move comptes whose block start date has arrived
    $schedule->job(new \App\Jobs\ArchiveScanJob)->everyFiveMinutes();

    // Run unarchive scan every 5 minutes to restore comptes whose block end date passed
    $schedule->job(new \App\Jobs\UnarchiveScanJob)->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
