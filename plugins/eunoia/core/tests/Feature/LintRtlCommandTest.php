<?php

use Illuminate\Support\Facades\File;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

it('fails on physical direction classes and passes on logical ones', function () {
    $dir = 'storage/framework/testing/rtl-'.uniqid();
    File::ensureDirectoryExists(base_path($dir));

    File::put(base_path("{$dir}/good.blade.php"), '<div class="ms-4 pe-2 text-start">ok</div>');
    $this->artisan('eunoia:lint-rtl', ['paths' => [$dir]])->assertSuccessful();

    File::put(base_path("{$dir}/bad.blade.php"), '<div class="ml-4 text-right">bad</div>');
    $this->artisan('eunoia:lint-rtl', ['paths' => [$dir]])->assertFailed();

    File::deleteDirectory(base_path($dir));
});
