<?php

namespace App\Observers;

use App\Services\AvailabilityService;
use App\Support\SlotClock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Chi nhánh/phòng thay đổi → xóa cache danh mục và lịch trống hôm nay. */
class CatalogObserver
{
    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        Cache::forget('catalog:branches');
        $branchId = $model->branch_id ?? $model->getKey();
        Cache::forget(AvailabilityService::cacheKey((int) $branchId, SlotClock::today()));
    }
}
