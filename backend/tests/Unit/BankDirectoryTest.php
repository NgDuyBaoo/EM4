<?php

use App\Services\BankDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config(['ngoi.vietqr' => [
        'bank_bin' => '970436', 'account' => '0123456789', 'account_name' => 'NGOI PHOTOBOOTH',
        'banks_url' => 'https://api.vietqr.io/v2/banks',
    ]]);
});

function vietqrBanks(): array
{
    return ['code' => '00', 'desc' => 'Get Bank list successful!', 'data' => [
        ['id' => 43, 'name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam', 'code' => 'VCB', 'bin' => '970436', 'shortName' => 'Vietcombank', 'logo' => 'https://api.vietqr.io/img/VCB.png'],
        ['id' => 17, 'name' => 'Ngân hàng TMCP Công thương Việt Nam', 'code' => 'ICB', 'bin' => '970415', 'shortName' => 'VietinBank', 'logo' => 'https://api.vietqr.io/img/ICB.png'],
    ]];
}

it('lấy danh sách ngân hàng từ VietQR và tra theo mã BIN', function () {
    Http::fake(['api.vietqr.io/*' => Http::response(vietqrBanks())]);

    expect(app(BankDirectory::class)->find('970415'))->toBe([
        'bin' => '970415', 'name' => 'Ngân hàng TMCP Công thương Việt Nam', 'short_name' => 'VietinBank',
        'code' => 'ICB', 'logo' => 'https://api.vietqr.io/img/ICB.png',
    ]);
    Http::assertSent(fn ($req) => $req->url() === 'https://api.vietqr.io/v2/banks');
});

it('ghép thông tin tài khoản nhận tiền của cửa hàng', function () {
    Http::fake(['api.vietqr.io/*' => Http::response(vietqrBanks())]);

    expect(app(BankDirectory::class)->shopAccount())->toMatchArray([
        'short_name' => 'Vietcombank', 'account' => '0123456789', 'account_name' => 'NGOI PHOTOBOOTH',
    ]);
});

it('gọi API một lần rồi dùng cache', function () {
    Http::fake(['api.vietqr.io/*' => Http::response(vietqrBanks())]);
    $dir = app(BankDirectory::class);

    $dir->find('970436');
    $dir->find('970415');

    Http::assertSentCount(1);
});

it('API lỗi hoặc trả mã lỗi thì trả null, không gọi lại liên tục', function ($response, int $calls) {
    Http::fake(['api.vietqr.io/*' => $response]);
    $dir = app(BankDirectory::class);

    expect($dir->shopAccount())->toBeNull()->and($dir->find('970436'))->toBeNull();
    Http::assertSentCount($calls); // lỗi mạng/5xx được thử lại 1 lần; sau đó dùng cache rỗng
})->with([
    'lỗi 500' => [fn () => Http::response('lỗi', 500), 2],
    'mã lỗi nghiệp vụ' => [fn () => Http::response(['code' => '99', 'desc' => 'lỗi']), 1],
]);
