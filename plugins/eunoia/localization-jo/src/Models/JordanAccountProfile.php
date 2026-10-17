<?php

namespace Eunoia\LocalizationJo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Account\Models\Account;

class JordanAccountProfile extends Model
{
    protected $table = 'eunoia_jo_account_profiles';

    protected $fillable = [
        'account_id',
        'name_en',
        'is_jordan_specific',
    ];

    protected $casts = [
        'is_jordan_specific' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
