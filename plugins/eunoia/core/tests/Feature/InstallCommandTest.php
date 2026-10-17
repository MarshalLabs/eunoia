<?php

use Webkul\PluginManager\Models\Plugin;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');
});

it('installs eunoia core for Jordan', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO'])
        ->assertSuccessful();

    expect(Plugin::query()->where('name', 'eunoia-core')->value('is_installed'))->toBeTruthy();
});

it('skips plugins that are already installed on a second run', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO'])->assertSuccessful();

    $this->artisan('eunoia:install', ['--country' => 'JO'])
        ->expectsOutputToContain(__('eunoia-core::install.already_installed', ['plugin' => 'eunoia-core']))
        ->assertSuccessful();
});

it('rejects an unsupported country', function () {
    $this->artisan('eunoia:install', ['--country' => 'XX'])
        ->assertFailed();
});

it('lists the plugins it would install for Jordan with --dry-run', function () {
    $this->artisan('eunoia:install', ['--country' => 'JO', '--dry-run' => true])
        ->expectsOutputToContain('eunoia-core')
        ->assertSuccessful();
});
