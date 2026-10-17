<?php

use Eunoia\Core\Support\PhoneNumber;

it('normalises Jordanian numbers to E.164 (JO-08)', function (string $input, string $e164) {
    expect(PhoneNumber::toE164($input))->toBe($e164);
})->with([
    'spec example' => ['0791234567', '+962791234567'],
    'with spaces'  => ['079 123 4567', '+962791234567'],
    'no zero'      => ['791234567', '+962791234567'],
    'landline'     => ['06 5123456', '+96265123456'],
    '00 prefix'    => ['00962791234567', '+962791234567'],
    'already e164' => ['+962791234567', '+962791234567'],
    'no plus'      => ['962791234567', '+962791234567'],
    'dashes'       => ['079-123-4567', '+962791234567'],
    'trunk zero'   => ['+962 (0)79 123 4567', '+962791234567'],
    'plus zero'    => ['+9620791234567', '+962791234567'],
    'saudi no +'   => ['966501234567', '+966501234567'],
    'ps mobile'    => ['0599123456', '+970599123456'],
]);

it('normalises Palestinian numbers with +970', function () {
    expect(PhoneNumber::toE164('0599123456', 'PS'))->toBe('+970599123456');
});

it('returns null for empty input', function () {
    expect(PhoneNumber::toE164(''))->toBeNull()
        ->and(PhoneNumber::toE164(null))->toBeNull();
});

it('displays local format', function () {
    expect(PhoneNumber::display('+962791234567'))->toBe('079 123 4567');
});

it('leaves short codes and extensions as typed', function () {
    expect(PhoneNumber::toE164('911'))->toBe('911')
        ->and(PhoneNumber::toE164('06 5123456 ext 12'))->toBe('06 5123456 ext 12');
});
