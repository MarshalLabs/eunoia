<?php

use Eunoia\Core\CoreServiceProvider;
use Eunoia\Core\EunoiaServiceProvider;
use Illuminate\Support\Facades\File;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

it('registers core first and reports providers whose class is missing', function () {
    $root = storage_path('framework/testing/eunoia-discovery-'.uniqid());

    $write = function (string $package, array $providers) use ($root): void {
        File::ensureDirectoryExists("{$root}/{$package}");
        File::put("{$root}/{$package}/composer.json", json_encode(['extra' => ['laravel' => ['providers' => $providers]]]));
    };

    $write('aaa', [EunoiaServiceProvider::class]);
    $write('core', [CoreServiceProvider::class]);
    $write('zzz', ['Eunoia\\Nope\\NopeServiceProvider']);

    $missing = [];
    $providers = EunoiaServiceProvider::discoverProviders($root, $missing);

    File::deleteDirectory($root);

    expect($providers)->toBe([CoreServiceProvider::class, EunoiaServiceProvider::class])
        ->and($missing)->toBe(['Eunoia\\Nope\\NopeServiceProvider']);
});

it('has registered the core provider in the app', function () {
    expect(app()->getProvider(CoreServiceProvider::class))->not->toBeNull();
});
