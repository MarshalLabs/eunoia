<?php

namespace Eunoia\JoFotara\Exceptions;

use RuntimeException;

class LiveModeNotAllowed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('eunoia-jofotara::jofotara.errors.live_not_allowed'));
    }
}
