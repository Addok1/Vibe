# Scheduler and cron

The application uses Laravel’s task scheduler for time-based jobs. **You must run the scheduler every minute via cron**; individual commands are registered in `app/Console/Kernel.php` and are not run by hand on a production server (except for one-off or debugging).

## Required cron entry

Add this to your server’s crontab (replace `/path-to-your-project` with the project root):

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and executes any scheduled tasks that are due.

## Where the schedule is defined

Scheduled tasks are defined in **`app/Console/Kernel.php`** in the `schedule()` method. The same file registers the Artisan commands. Laravel 11+ can also define schedules in `routes/console.php`; this project uses the Kernel.

## List of scheduled commands

| Schedule        | Command                         | Purpose |
|----------------|----------------------------------|--------|
| Every minute   | `drivers:totrip`                | Change driver status to “on trip” where applicable. |
| Every minute   | `assign_drivers:for_regular_rides` | Assign drivers for regular (instant) ride requests. |
| Every 5 minutes| `assign_drivers:for_schedule_rides` | Assign drivers for scheduled rides. |
| Every 5 minutes| `offline:drivers`               | Mark drivers offline / unavailable when appropriate. |
| Daily          | `notify:document:expires`       | Notify drivers (e.g. email/push) about expiring documents. |
| Every 5 minutes| `expire:subscription`           | Expire subscription plans and update driver status. |
| Every 5 minutes| `clear:otp`                     | Clear expired OTPs. |
| Every minute   | `cancel:request`                | Cancel requests that were not accepted within the allowed time (e.g. ~15 minutes for biding). |
| Every minute   | `promotion:deactivate-expired`  | Deactivate expired promotions. |

Optional / one-off (not scheduled):

- **`clear:request`** – Clear request table (e.g. demo/maintenance).
- **`clear:database`** – Clear demo database (demo mode).

## Running the scheduler manually

To run the scheduler once (e.g. to test):

```bash
php artisan schedule:run
```

To run a single command:

```bash
php artisan cancel:request
php artisan expire:subscription
# etc.
```

To see the list of scheduled tasks:

```bash
php artisan schedule:list
```

## Queue worker

Scheduled commands may dispatch jobs. Ensure a queue worker is running when using queue drivers other than `sync`:

```bash
php artisan queue:work
```

See the main [README.md](../README.md) for queue and cron setup in “Queues and scheduler”.
