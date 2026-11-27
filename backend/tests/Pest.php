<?php

use App\Models\Branch;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/** Cố định "bây giờ" là hôm nay lúc $time giờ Việt Nam. */
function freezeAt(string $time = '10:00', ?string $date = null): Carbon
{
    $at = Carbon::parse(($date ?? '2026-10-16').' '.$time, 'Asia/Ho_Chi_Minh');
    test()->travelTo($at);

    return $at;
}

/** 2 chi nhánh × 3 phòng như dữ liệu thật. @return array{0: Branch, 1: Branch} */
function seedBranches(): array
{
    $branches = [];
    foreach (['b1' => '36 Nguyễn Sinh Cung', 'b2' => '88 Đinh Tiên Hoàng'] as $code => $name) {
        $b = Branch::factory()->create(['code' => $code, 'name' => $name]);
        foreach (['Vintage', 'Classic', 'Film'] as $i => $style) {
            Room::factory()->for($b)->create(['name' => 'Phòng '.($i + 1), 'style' => $style, 'sort_order' => $i + 1, 'price_per_slot' => 50000]);
        }
        $branches[] = $b->fresh();
    }

    return $branches;
}

function owner(): User
{
    return User::factory()->owner()->create();
}

function staff(): User
{
    return User::factory()->staff()->create();
}

function customer(): User
{
    return User::factory()->create();
}

function todayCode(): string
{
    return app(\App\Services\AccessCodeService::class)->today()->code;
}
