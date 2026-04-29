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
        $schedule->command('orders:send-tomorrow')->twiceDailyAt('11,16,0');
        $schedule->command('orders:send-today')->dailyAt('02:00');
        $schedule->command('app:sync-deliveo-statuses')->fridays()->at('16:00');
        $schedule->command('app:sync-success-deliveo-statuses')->hourly();
        $schedule->command('get-deliveo-pending-orders')->everyFifteenMinutes()->between('10:00', '16:00');
        $schedule->command('salesrender:update-status')->everyMinute();

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
