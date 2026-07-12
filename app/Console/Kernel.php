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
        $schedule->command("app:verify-payment")
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('finance:close-invoices')
            ->monthlyOn(5, '00:05')->withoutOverlapping()->onOneServer();
        $schedule->command('finance:close-invoices')
            ->monthlyOn(20, '00:05')->withoutOverlapping()->onOneServer();

        $schedule->command('btg:refresh-token')
            ->dailyAt('03:00')->withoutOverlapping()->onOneServer();
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
