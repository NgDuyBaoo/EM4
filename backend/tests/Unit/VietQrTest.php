<?php

use App\Services\VietQr;

it('tạo link ảnh QR VietQR đúng ngân hàng, số tiền và nội dung', function () {
    config(['ngoi.vietqr' => ['bank_bin' => '970436', 'account' => '0123456789', 'account_name' => 'NGOI PHOTOBOOTH']]);

    $url = VietQr::imageUrl(150000, 'NGAB12CD');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($url)->toStartWith('https://img.vietqr.io/image/970436-0123456789-compact2.png?')
        ->and($query)->toBe(['amount' => '150000', 'addInfo' => 'NGAB12CD', 'accountName' => 'NGOI PHOTOBOOTH']);
});
