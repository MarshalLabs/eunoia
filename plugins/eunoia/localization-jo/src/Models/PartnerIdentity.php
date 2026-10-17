<?php

namespace Eunoia\LocalizationJo\Models;

use Eunoia\LocalizationJo\Enums\IdentityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Webkul\Partner\Models\Partner;

class PartnerIdentity extends Model
{
    protected $table = 'eunoia_partner_identities';

    protected $fillable = [
        'partner_id',
        'type',
        'value',
        'country_code',
        'verified_at',
    ];

    protected $casts = [
        'type'        => IdentityType::class,
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (PartnerIdentity $identity): void {
            $identity->value = IdentityType::clean((string) $identity->value);

            if (! $identity->type->isValid($identity->value)) {
                throw new InvalidArgumentException(__('eunoia-localization-jo::identities.invalid', ['type' => $identity->type->getLabel()]));
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
