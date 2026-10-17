<?php

use Webkul\PluginManager\Package;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Services\BootLoader;
use Webkul\Support\Models\Currency;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';
require_once dirname(__DIR__, 5).'/plugins/webkul/inventories/tests/Helpers/InventoryHelper.php';
require_once dirname(__DIR__, 5).'/plugins/webkul/point-of-sale/tests/Helpers/PosHelper.php';

/**
 * F1 / JO-01 acceptance: POS maths works in JOD (3 decimals, fils) once patch P1 is applied.
 */
beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    /** Plugin installs elsewhere in the run reset Postgres sequences while seed rows use explicit ids. */
    Package::syncPostgresSequences();

    InventoryHelper::actingAsAdmin();

    $this->jod = Currency::query()->where('name', 'JOD')->firstOrFail();
    $this->warehouse = InventoryHelper::warehouse();
    $this->session = PosHelper::openSession($this->warehouse);
    $this->config = $this->session->config;
    $this->config->forceFill(['currency_id' => $this->jod->id])->saveQuietly();
    $this->session->forceFill(['currency_id' => $this->jod->id])->saveQuietly();

    $this->product = InventoryHelper::product(['price' => 1.255]);
    $this->product->productTaxes()->detach();
    $this->discountProduct = InventoryHelper::product(['price' => 0.0, 'name' => 'Discount']);
});

function jodDraftOrder($session, $config, $product): Order
{
    $order = Order::create([
        'session_id' => $session->id,
        'config_id'  => $config->id,
    ]);

    $order->lines()->create([
        'product_id' => $product->id,
        'qty'        => 3,
        'price_unit' => 1.255,
    ]);

    return PointOfSale::recomputeOrder($order->refresh());
}

it('sends the till a positive JOD rounding of 0.001', function () {
    $payload = app(BootLoader::class)->load($this->config->refresh(), $this->session->refresh());

    $jod = collect($payload['currencies'] ?? [])->firstWhere('name', 'JOD');

    expect($jod)->not->toBeNull()
        ->and($jod['rounding'])->toBe(0.001)
        ->and($jod['decimal_places'])->toBe(3);
});

it('computes 1.255 JOD x 3 = 3.765 JOD on a POS order', function () {
    $order = jodDraftOrder($this->session, $this->config->refresh(), $this->product);

    expect($order->currency_id)->toBe($this->jod->id)
        ->and((float) $order->lines()->first()->price_subtotal)->toBe(3.765)
        ->and((float) $order->amount_total)->toBe(3.765);
});

it('applies a 10% discount rounded to the fils', function () {
    $this->config->update([
        'enable_global_discount' => true,
        'discount_product_id'    => $this->discountProduct->id,
    ]);

    $order = PointOfSale::applyGlobalDiscount(jodDraftOrder($this->session, $this->config->refresh(), $this->product), 10);

    $discount = (float) $order->lines()->where('product_id', $this->discountProduct->id)->value('price_unit');
    $total = (float) $order->amount_total;

    /** Upstream OrderCalculator keeps 4 decimals (finding F26); documents must round to fils. */
    expect($discount)->toBe(-0.3765)
        ->and($total)->toBe(3.3885)
        ->and(float_compare(float_round($total, precisionRounding: (float) $this->jod->rounding), 3.389, precisionDigits: 6))->toBe(0);
});
