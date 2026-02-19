<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('check:webshippy-order-status')->everyMinute();
        $schedule->command('telescope:prune --hours=668')->daily();
        $schedule->command('orders:send-tomorrow')->dailyAt('10:00');
        $schedule->command('app:sync-deliveo-statuses')->fridays()->at('16:00');
        $schedule->command('app:sync-success-deliveo-statuses')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
