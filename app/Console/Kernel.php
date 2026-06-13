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
        $schedule->command('logs:purge-metier')
            ->dailyAt('02:30')
            ->withoutOverlapping();

        if (config('crons.emails_enabled')) {
            $schedule->command('planning:send-weekly')
                ->weeklyOn(6, '12:00')
                ->withoutOverlapping();

            $schedule->command('conges:notify-pending')
                ->cron('30 8 */5 * *')
                ->withoutOverlapping();
        }
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
