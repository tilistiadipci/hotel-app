<?php

namespace App\Console\Commands;

use App\Services\HotelLicenseLifecycle;
use Illuminate\Console\Command;

class ExpireHotelTrials extends Command
{
    protected $signature = 'hotels:expire-trials';

    protected $description = 'Expire trial licenses and deactivate their hotels';

    public function handle(HotelLicenseLifecycle $lifecycle): int
    {
        $count = $lifecycle->expireDueTrials();
        $this->info("{$count} hotel trial dinonaktifkan.");

        return self::SUCCESS;
    }
}
