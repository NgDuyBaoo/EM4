<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;

it('health check vẫn 200 khi cache trả số dưới dạng chuỗi như Redis', function () {
    // RedisStore lưu số nguyên dạng chuỗi và đọc ra "1" thay vì 1.
    Cache::extend('redis-like', fn () => Cache::repository(new class extends ArrayStore
    {
        public function get($key)
        {
            $value = parent::get($key);

            return is_int($value) ? (string) $value : $value;
        }
    }));
    config(['cache.stores.redis-like' => ['driver' => 'redis-like'], 'cache.default' => 'redis-like']);
    Cache::forgetDriver('redis-like');

    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('checks.cache.ok', true);
});
