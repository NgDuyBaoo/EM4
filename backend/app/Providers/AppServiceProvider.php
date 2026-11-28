<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStaff;
use App\Livewire\Pulse\Network as PulseNetworkCard;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Room;
use App\Models\User;
use App\Monitoring\SlowQueryAlert;
use App\Observers\BookingObserver;
use App\Observers\CatalogObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Chống antipattern: báo lỗi khi lazy-load (N+1), gán thuộc tính không tồn tại… (chỉ khi dev/test)
        Model::shouldBeStrict(! $this->app->isProduction());

        Booking::observe(BookingObserver::class);
        Room::observe(CatalogObserver::class);
        Branch::observe(CatalogObserver::class);

        $this->configureSlowQueryAlerts();
        $this->configureRateLimits();
        $this->configureApiDocs();

        // Mọi request cập nhật component Livewire (gọi action) đều chạy lại middleware kiểm quyền của trang
        Livewire::addPersistentMiddleware([EnsureStaff::class]);
        // Thẻ "Mạng" trên dashboard /pulse
        Livewire::component('ngoi-pulse-network', PulseNetworkCard::class);

        // Prefetch asset Vite để trang tải nhanh hơn
        Vite::prefetch(concurrency: 3);

        // Telescope, Pulse, tài liệu API: chỉ chủ cửa hàng (ở production)
        Gate::define('viewPulse', fn (User $user) => $user->isOwner());
        Gate::define('viewApiDocs', fn (?User $user = null) => ! $this->app->isProduction() || $user?->isOwner());
    }

    /** Cảnh báo truy vấn chậm: từng câu > NGOI_SLOW_QUERY_MS, hoặc tổng thời gian query của 1 request > 2 giây. Ghi log và báo cho chủ tiệm. */
    private function configureSlowQueryAlerts(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if ($query->time >= (int) config('ngoi.slow_query_ms')) {
                Log::channel('slow-query')->warning('Truy vấn chậm', [
                    'ms' => $query->time,
                    'sql' => $query->sql,
                    // Không ghi bindings: có thể chứa tên, số điện thoại, email khách. Câu SQL giữ dấu ? là đủ để tra.
                    'bindings_count' => count($query->bindings),
                    'url' => request()?->fullUrl(),
                ]);
                SlowQueryAlert::report('query', $query->sql, $query->time, request()?->fullUrl());
            }
        });

        DB::whenQueryingForLongerThan(2000, function (Connection $connection, QueryExecuted $event) {
            Log::channel('slow-query')->error('Tổng thời gian truy vấn của request vượt 2 giây', [
                'connection' => $connection->getName(),
                'total_ms' => $connection->totalQueryDuration(),
                'url' => request()?->fullUrl(),
            ]);
            SlowQueryAlert::report('request', $event->sql, $connection->totalQueryDuration(), request()?->fullUrl());
        });
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?: $r->ip()));
        // Chống dò mã truy cập 6 số / mật khẩu
        RateLimiter::for('access-code', fn (Request $r) => Limit::perMinutes(10, 20)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
    }

    private function configureApiDocs(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer'));
            });
    }
}
