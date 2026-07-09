<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\ChangeDriversToTrips;
use App\Console\Commands\OfflineUnAvailableDrivers;
use App\Console\Commands\NotifyDriverDocumentExpiry;
use App\Console\Commands\AssignDriversForScheduledRides;
use App\Console\Commands\AssignDriversForRegularRides;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ClearDemoDatabase;
use App\Console\Commands\ClearRequestTable;
use App\Console\Commands\ClearOtp;
use App\Console\Commands\CancelRequests;
use App\Console\Commands\ExpireSubscription;
use App\Console\Commands\DeactivateExpiredPromotions;
use App\Console\Commands\AutoApproveDemoDriverDocuments;
use App\Console\Commands\AutoApproveDemoOwnerDocuments;
use App\Console\Commands\AutoApproveDemoUserDocuments;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ChangeDriversToTrips::class,
        NotifyDriverDocumentExpiry::class,
        AssignDriversForScheduledRides::class,
        OfflineUnAvailableDrivers::class,
        AssignDriversForRegularRides::class,
        ClearDemoDatabase::class,
        ClearRequestTable::class,
        ExpireSubscription::class,
        ClearOtp::class,
        CancelRequests::class,
        DeactivateExpiredPromotions::class,
        AutoApproveDemoDriverDocuments::class,
        AutoApproveDemoOwnerDocuments::class,
        AutoApproveDemoUserDocuments::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('drivers:totrip')
                 ->everyMinute();
         $schedule->command('assign_drivers:for_regular_rides')
                 ->everyMinute();
         $schedule->command('assign_drivers:for_schedule_rides')
                 ->everyMinute();
         $schedule->command('offline:drivers')
                 ->everyFiveMinutes();
         $schedule->command('notify:document:expires')
                 ->daily();
        $schedule->command('expire:subscription')->everyFiveMinutes();
         $schedule->command('clear:otp')
                 ->everyFiveMinutes();
         $schedule->command('cancel:request')
                 ->everyMinute();      
                 
        $schedule->command('promotion:deactivate-expired')->everyMinute();

        $schedule->command('demo:approve-driver-documents')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('demo:approve-owner-documents')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('demo:approve-user-documents')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
