<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class CancelNoShows extends Command
{
    protected $signature = 'bookings:cancel-no-shows';

    protected $description = 'Hủy các booking quá giờ hẹn mà khách chưa check-in (BR-07)';

    public function handle(BookingService $service): int
    {
        $count = $service->cancelNoShows();
        $this->info("Đã hủy {$count} booking quá giờ.");

        return self::SUCCESS;
    }
}
