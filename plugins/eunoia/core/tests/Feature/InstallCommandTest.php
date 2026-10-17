<?php

use Webkul\PluginManager\Models\Plugin;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';
beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');
});

it('installs eunoia core for Jordan', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO', '--no-interaction' => true])
        ->assertSuccessful();

    expect(Plugin::query()->where('name', 'eunoia-core')->value('is_installed'))->toBeTruthy();
});

it('is safe to run twice', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO', '--no-interaction' => true])->assertSuccessful();
    $this->artisan('eunoia:install', ['--country' => 'JO', '--no-interaction' => true])->assertSuccessful();

    expect(Plugin::query()->where('name', 'eunoia-core')->count())->toBe(1);
});

it('rejects an unsupported country', function () {
    $this->artisan('eunoia:install', ['--country' => 'XX', '--no-interaction' => true])
        ->assertFailed();
});

it('lists the plugins it would install for Jordan with --dry-run', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO', '--dry-run' => true])
        ->expectsOutputToContain('eunoia-core')
        ->assertSuccessful();
});
