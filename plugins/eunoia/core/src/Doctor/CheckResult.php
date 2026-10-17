<?php

namespace Eunoia\Core\Doctor;

class CheckResult
{
    public function __construct(
        public readonly CheckStatus $status,
        public readonly string $message,
    ) {}

    public static function ok(string $message): self
    {
        return new self(CheckStatus::Ok, $message);
    }

    public static function warn(string $message): self
    {
        return new self(CheckStatus::Warn, $message);
    }

    public static function fail(string $message): self
    {
        return new self(CheckStatus::Fail, $message);
    }

    public static function skip(string $message): self
    {
        return new self(CheckStatus::Skip, $message);
    }
}
