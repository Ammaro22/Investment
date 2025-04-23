<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        Log::info('Scheduler function is being called at ' . now());

        $schedule->job(new \App\Jobs\TransferProfitJob())
            ->dailyAt('00:00')
            ->timezone('Asia/Damascus');

        Log::info('Scheduled job has been added.');
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
