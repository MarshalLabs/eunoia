<?php

namespace Eunoia\Core\Doctor;

class CheckResult
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    public const SKIP = 'skip';

    public function __construct(
        public readonly string $status,
        public readonly string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(self::OK, $message);
    }

    public static function warn(string $message): self
    {
        return new self(self::WARN, $message);
    }

    public static function fail(string $message): self
    {
        return new self(self::FAIL, $message);
    }

    public static function skip(string $message): self
    {
        return new self(self::SKIP, $message);
    }
}
