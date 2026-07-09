<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Promotion\PromotionTemplate;

class DeactivateExpiredPromotions extends Command
{
    protected $signature = 'promotion:deactivate-expired';
    protected $description = 'Deactivate expired promotion templates';

    public function handle()
    {
        $now = Carbon::now();

        $count = PromotionTemplate::where('active', true)
            ->where('to', '<', $now)
            ->update(['active' => false]);

        $this->info("Deactivated {$count} expired promotions.");
    }
}
