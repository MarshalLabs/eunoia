<?php

namespace Eunoia\JoFotara\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Queued = 'queued';

    case Sending = 'sending';

    case Accepted = 'accepted';

    case Rejected = 'rejected';

    case Failed = 'failed';

    /**
     * Once a posted invoice is queued it may not be edited or reset to draft (JF-07); only
     * rejected/failed documents can be corrected and posted again (same ICV and UUID).
     */
    public function isLocked(): bool
    {
        return in_array($this, [self::Queued, self::Sending, self::Accepted], true);
    }

    public function getLabel(): string
    {
        return __('eunoia-jofotara::jofotara.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Accepted               => 'success',
            self::Rejected, self::Failed => 'danger',
            self::Queued, self::Sending  => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Accepted              => 'heroicon-o-check-circle',
            self::Rejected              => 'heroicon-o-x-circle',
            self::Failed                => 'heroicon-o-exclamation-triangle',
            self::Queued, self::Sending => 'heroicon-o-clock',
        };
    }
}
