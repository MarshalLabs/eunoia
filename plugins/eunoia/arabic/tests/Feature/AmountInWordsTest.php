<?php

use Eunoia\Arabic\AmountInWords;

it('writes JOD amounts in Arabic words (AR-05)', function (float $amount, string $words) {
    expect(AmountInWords::arabic($amount, 'JOD'))->toBe($words);
})->with([
    'spec example' => [3.765, 'فقط ثلاثة دنانير وسبعمائة وخمسة وستون فلساً لا غير'],
    'one'          => [1, 'فقط دينار واحد لا غير'],
    'two'          => [2, 'فقط ديناران لا غير'],
    'eleven'       => [11, 'فقط أحد عشر ديناراً لا غير'],
    'hundred'      => [100, 'فقط مائة دينار لا غير'],
    'thousand'     => [1000, 'فقط ألف دينار لا غير'],
    'fils only'    => [0.25, 'فقط مائتان وخمسون فلساً لا غير'],
    'one fils'     => [0.001, 'فقط فلس واحد لا غير'],
    'two fils'     => [0.002, 'فقط فلسان لا غير'],
    'ten fils'     => [0.01, 'فقط عشرة فلوس لا غير'],
    'zero'         => [0, 'فقط صفر دينار لا غير'],
    'rounds fils'  => [1.2345, 'فقط دينار واحد ومائتان وخمسة وثلاثون فلساً لا غير'],
    'two hundred'  => [200, 'فقط مائتا دينار لا غير'],
    'two thousand' => [2000, 'فقط ألفا دينار لا غير'],
    'two million'  => [2000000, 'فقط مليونا دينار لا غير'],
    '1200'         => [1200, 'فقط ألف ومائتا دينار لا غير'],
]);

it('writes ILS with feminine agorot', function (float $amount, string $words) {
    expect(AmountInWords::arabic($amount, 'ILS'))->toBe($words);
})->with([
    [12.5, 'فقط اثنا عشر شيكلاً وخمسون أغورة لا غير'],
    [3.03, 'فقط ثلاثة شواكل وثلاث أغورات لا غير'],
    [1.01, 'فقط شيكل واحد وأغورة واحدة لا غير'],
    [2.02, 'فقط شيكلان وأغورتان لا غير'],
]);

it('writes USD with cents', function () {
    expect(AmountInWords::arabic(100.25, 'USD'))->toBe('فقط مائة دولار وخمسة وعشرون سنتاً لا غير');
});

it('handles negative amounts as a credit', function () {
    expect(AmountInWords::arabic(-2, 'JOD'))->toBe('فقط سالب ديناران لا غير');
});

it('writes English words for English documents', function () {
    expect(AmountInWords::english(3.765, 'JOD'))->toBe('Three Jordanian dinars and seven hundred sixty-five fils only')
        ->and(AmountInWords::english(1, 'JOD'))->toBe('One Jordanian dinar only')
        ->and(AmountInWords::english(0.5, 'JOD'))->toBe('Five hundred fils only')
        ->and(AmountInWords::english(1.01, 'USD'))->toBe('One US dollar and one cent only');
});

it('rejects unknown currencies', function () {
    AmountInWords::arabic(1, 'XYZ');
})->throws(InvalidArgumentException::class);
