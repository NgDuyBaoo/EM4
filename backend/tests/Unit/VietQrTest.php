<?php

use App\Services\VietQr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('tạo link ảnh QR VietQR đúng ngân hàng, số tiền và nội dung', function () {
    config(['ngoi.vietqr' => ['bank_bin' => '970436', 'account' => '0123456789', 'account_name' => 'NGOI PHOTOBOOTH']]);

    $url = VietQr::imageUrl(150000, 'NGAB12CD');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($url)->toStartWith('https://img.vietqr.io/image/970436-0123456789-compact2.png?')
        ->and($query)->toBe(['amount' => '150000', 'addInfo' => 'NGAB12CD', 'accountName' => 'NGOI PHOTOBOOTH']);
});

it('có khóa API thì gọi VietQR generate kèm header xác thực và trả ảnh data URL', function () {
    Cache::flush();
    config([
        'ngoi.vietqr' => ['bank_bin' => '970436', 'account' => '0123456789', 'account_name' => 'NGOI PHOTOBOOTH'],
        'services.vietqr' => ['client_id' => 'cid-123', 'api_key' => 'key-456', 'generate_url' => 'https://api.vietqr.io/v2/generate'],
    ]);
    Http::fake(['api.vietqr.io/v2/generate' => Http::response([
        'code' => '00', 'desc' => 'Gen VietQR successful!', 'data' => ['qrDataURL' => 'data:image/png;base64,iVBORw0KGgo='],
    ])]);

    expect(VietQr::src(150000, 'NGAB12CD'))->toBe('data:image/png;base64,iVBORw0KGgo=');

    Http::assertSent(fn ($req) => $req->hasHeader('x-client-id', 'cid-123')
        && $req->hasHeader('x-api-key', 'key-456')
        && $req['acqId'] === 970436 && $req['accountNo'] === '0123456789'
        && $req['amount'] === 150000 && $req['addInfo'] === 'NGAB12CD');
});

it('chưa có khóa hoặc API lỗi thì dùng link ảnh nhanh', function (?string $key, $response) {
    Cache::flush();
    config([
        'ngoi.vietqr' => ['bank_bin' => '970436', 'account' => '0123456789', 'account_name' => 'NGOI'],
        'services.vietqr' => ['client_id' => $key, 'api_key' => $key, 'generate_url' => 'https://api.vietqr.io/v2/generate'],
    ]);
    Http::fake(['*' => $response]);

    expect(VietQr::src(50000, 'NGX'))->toStartWith('https://img.vietqr.io/image/970436-0123456789-compact2.png');
})->with([
    'chưa có khóa' => [null, fn () => Http::response([])],
    'khóa sai (401)' => ['sai', fn () => Http::response(['code' => '11', 'desc' => 'Unauthorized'], 401)],
    'mã lỗi nghiệp vụ' => ['k', fn () => Http::response(['code' => '21', 'desc' => 'Invalid account'])],
]);
