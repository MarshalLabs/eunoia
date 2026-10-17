<?php

namespace Eunoia\Core\Doctor;

enum CheckStatus: string
{
    case Ok = 'ok';

    case Warn = 'warn';

    case Fail = 'fail';

    case Skip = 'skip';

    public function icon(): string
    {
        return match ($this) {
            self::Ok   => '✓',
            self::Warn => '!',
            self::Fail => '✕',
            self::Skip => '–',
        };
    }

    public function getLabel(): string
    {
        return __('eunoia-core::doctor.status.'.$this->value);
    }
}
