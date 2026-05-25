<?php

declare(strict_types=1);

namespace Modules\Core\Application\Results;

final class Result
{
    private function __construct(
        private readonly bool $successful,
        private readonly mixed $value = null,
        private readonly ?Error $error = null,
    ) {
    }

    public static function success(mixed $value = null): self
    {
        return new self(true, $value, null);
    }

    public static function failure(Error $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function isFailure(): bool
    {
        return ! $this->successful;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function error(): ?Error
    {
        return $this->error;
    }
}
