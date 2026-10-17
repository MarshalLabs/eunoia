<?php

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    /** Upstream caches installed plugins statically; DB rolls back between tests, so refresh it (as upstream tests do). */
    Package::$plugins = Plugin::all()->keyBy('name');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');
});

/**
 * Runs with a real (buffered) console: upstream install commands may ask "Reseed / Skip" for already
 * installed dependencies, which a mocked PendingCommand console cannot answer.
 */
function runEunoiaInstall(): array
{
    $output = new BufferedOutput;
    $code = app(Kernel::class)->call('eunoia:install', ['--country' => 'JO'], $output);

    return [$code, $output->fetch()];
}

it('installs eunoia core for Jordan', function () {
    [$code] = runEunoiaInstall();

    expect($code)->toBe(0);

    expect(Plugin::query()->where('name', 'eunoia-core')->value('is_installed'))->toBeTruthy();
});

it('skips plugins that are already installed on a second run', function () {
    expect(runEunoiaInstall()[0])->toBe(0);

    [$code, $output] = runEunoiaInstall();

    expect($code)->toBe(0)
        ->and($output)->toContain(__('eunoia-core::install.already_installed', ['plugin' => 'eunoia-core']));
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
