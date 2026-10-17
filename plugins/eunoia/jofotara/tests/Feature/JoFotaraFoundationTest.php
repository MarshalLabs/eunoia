<?php

use Eunoia\JoFotara\Enums\DocumentStatus;
use Eunoia\JoFotara\Enums\SellerRegistration;
use Eunoia\JoFotara\Exceptions\LiveModeNotAllowed;
use Eunoia\JoFotara\Filament\Pages\ManageJoFotara;
use Eunoia\JoFotara\Services\IcvAllocator;
use Eunoia\JoFotara\Services\InvoiceTypeCodeResolver;
use Eunoia\JoFotara\Settings\JoFotaraSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Models\Move;
use Webkul\PluginManager\Package;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    Package::syncPostgresSequences();
    Artisan::call('eunoia-jofotara:install', ['--no-interaction' => true]);
});

it('stores Client-Id and Secret-Key encrypted (JF-01)', function () {
    $settings = app(JoFotaraSettings::class);
    $settings->client_id = 'fake-client-id';
    $settings->secret_key = 'fake-secret-key-123';
    $settings->save();

    $raw = DB::table('settings')->where('group', 'eunoia_jofotara')->whereIn('name', ['client_id', 'secret_key'])->pluck('payload')->implode(' ');

    expect($raw)->not->toContain('fake-client-id')->not->toContain('fake-secret-key-123')
        ->and(app(JoFotaraSettings::class)->refresh()->secret_key)->toBe('fake-secret-key-123');
});

it('defaults to the local mock and refuses live mode outside production (JF-09)', function () {
    $settings = app(JoFotaraSettings::class);

    expect($settings->mode)->toBe('mock')
        ->and($settings->effectiveBaseUrl())->toBe(config('eunoia-jofotara.mock_base_url'));

    $settings->mode = 'live';

    expect(fn () => $settings->effectiveBaseUrl())->toThrow(LiveModeNotAllowed::class);
});

it('allocates gap-free ICVs per company', function () {
    $allocator = app(IcvAllocator::class);

    expect([$allocator->next(1), $allocator->next(1), $allocator->next(1), $allocator->next(2)])->toBe([1, 2, 3, 1]);
});

it('never gives two processes the same ICV (JF-04)', function () {
    $connection = 'icv_concurrency';
    config(["database.connections.{$connection}" => config('database.connections.'.config('database.default'))]);
    $db = DB::connection($connection);
    $db->statement('create table if not exists eunoia_jofotara_counters_concurrency (company_id bigint primary key, next_icv bigint not null default 1)');
    $db->table('eunoia_jofotara_counters_concurrency')->delete();

    $workers = 10;
    $perWorker = 5;
    $pids = [];

    for ($i = 0; $i < $workers; $i++) {
        $pid = pcntl_fork();

        if ($pid === 0) {
            DB::purge($connection);
            DB::connection($connection)->setPdo(null)->reconnect();
            $allocator = new IcvAllocator($connection, 'eunoia_jofotara_counters_concurrency');
            $numbers = [];

            for ($j = 0; $j < $perWorker; $j++) {
                $numbers[] = $allocator->next(424242);
            }

            file_put_contents(storage_path("framework/testing/icv-{$i}.json"), json_encode($numbers));

            /** SIGKILL: skip destructors so the child does not close the parent's inherited DB socket. */
            posix_kill(getmypid(), SIGKILL);
        }

        $pids[] = $pid;
    }

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }

    DB::purge($connection);
    $all = [];

    for ($i = 0; $i < $workers; $i++) {
        $file = storage_path("framework/testing/icv-{$i}.json");
        $all = [...$all, ...json_decode((string) file_get_contents($file), true)];
        @unlink($file);
    }

    sort($all);
    DB::connection($connection)->statement('drop table eunoia_jofotara_counters_concurrency');

    expect($all)->toBe(range(1, $workers * $perWorker));
})->skip(fn () => ! function_exists('pcntl_fork'), 'pcntl not available');

it('computes the invoice type code from registration and settlement (JF-03)', function (SellerRegistration $registration, bool $cash, string $code) {
    $move = new Move(['move_type' => MoveType::OUT_INVOICE]);

    expect(app(InvoiceTypeCodeResolver::class)->resolve($move, $registration, $cash))->toBe($code);
})->with([
    [SellerRegistration::Income, true, '011'],
    [SellerRegistration::Income, false, '021'],
    [SellerRegistration::GeneralSales, true, '012'],
    [SellerRegistration::GeneralSales, false, '022'],
    [SellerRegistration::SpecialSales, true, '013'],
    [SellerRegistration::SpecialSales, false, '023'],
]);

it('treats an invoice due on its date as cash and a later due date as receivable', function () {
    $resolver = app(InvoiceTypeCodeResolver::class);

    expect($resolver->isCash(new Move(['invoice_date' => '2026-10-17', 'invoice_date_due' => '2026-10-17'])))->toBeTrue()
        ->and($resolver->isCash(new Move(['invoice_date' => '2026-10-17', 'invoice_date_due' => '2026-11-16'])))->toBeFalse();
});

it('knows document statuses', function () {
    expect(DocumentStatus::Accepted->isLocked())->toBeTrue()
        ->and(DocumentStatus::Sending->isLocked())->toBeTrue()
        ->and(DocumentStatus::Rejected->isLocked())->toBeFalse()
        ->and(DocumentStatus::Queued->isLocked())->toBeTrue()
        ->and(DocumentStatus::Failed->isLocked())->toBeFalse();
});

it('never sends stored secrets to the browser and keeps them when left empty (JF-01)', function () {
    require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/FilamentHelper.php';

    URL::resolveMissingNamedRoutesUsing(fn () => '#');
    FilamentHelper::actingAs(['page_eunoia_jofotara_manage']);

    $settings = app(JoFotaraSettings::class);
    $settings->client_id = 'fake-client-id';
    $settings->secret_key = 'fake-secret-key-123';
    $settings->save();

    Livewire\Livewire::test(ManageJoFotara::class)
        ->assertOk()
        ->assertDontSee('fake-secret-key-123')
        ->assertDontSee('fake-client-id')
        ->assertSet('data.secret_key', null)
        ->set('data.tin', '12345678')
        ->set('data.income_source_sequence', '1234567')
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(JoFotaraSettings::class)->refresh();

    expect($settings->secret_key)->toBe('fake-secret-key-123')
        ->and($settings->client_id)->toBe('fake-client-id')
        ->and($settings->tin)->toBe('12345678');
});
