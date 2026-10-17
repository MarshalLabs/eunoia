<?php

namespace Eunoia\LocalizationJo\Models;

use Eunoia\LocalizationJo\Enums\JoFotaraTaxCategory;
use Eunoia\LocalizationJo\Enums\JordanTaxKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Account\Models\Tax;

class JordanTaxProfile extends Model
{
    protected $table = 'eunoia_jo_tax_profiles';

    protected $fillable = [
        'tax_id',
        'kind',
        'jofotara_category',
        'name_en',
    ];

    protected $casts = [
        'kind'              => JordanTaxKind::class,
        'jofotara_category' => JoFotaraTaxCategory::class,
    ];

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
