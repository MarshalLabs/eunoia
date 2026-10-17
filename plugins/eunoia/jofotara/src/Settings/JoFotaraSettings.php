<?php

namespace Eunoia\JoFotara\Settings;

use Eunoia\JoFotara\Enums\SellerRegistration;
use Eunoia\JoFotara\Exceptions\LiveModeNotAllowed;
use Spatie\LaravelSettings\Settings;

/**
 * JoFotara company settings (JF-01). Client-Id and Secret-Key are encrypted at rest and never shown again.
 */
class JoFotaraSettings extends Settings
{
    public ?string $client_id;

    public ?string $secret_key;

    public ?string $tin;

    public ?string $income_source_sequence;

    public ?string $seller_name;

    public string $seller_registration;

    public string $mode;

    public bool $auto_submit;

    public static function group(): string
    {
        return 'eunoia_jofotara';
    }

    /**
     * @return array<int, string>
     */
    public static function encrypted(): array
    {
        return ['client_id', 'secret_key'];
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    /**
     * The endpoint to call. Live is refused unless explicitly allowed on this server (never in dev/CI).
     */
    public function effectiveBaseUrl(): string
    {
        if (! $this->isLive()) {
            return rtrim((string) config('eunoia-jofotara.mock_base_url'), '/');
        }

        if (! config('eunoia-jofotara.allow_live') || ! app()->isProduction()) {
            throw new LiveModeNotAllowed;
        }

        return rtrim((string) config('eunoia-jofotara.live_base_url'), '/');
    }

    public function registration(): SellerRegistration
    {
        return SellerRegistration::tryFrom($this->seller_registration) ?? SellerRegistration::GeneralSales;
    }

    public function isConfigured(): bool
    {
        return filled($this->tin) && filled($this->income_source_sequence) && filled($this->client_id) && filled($this->secret_key);
    }
}
