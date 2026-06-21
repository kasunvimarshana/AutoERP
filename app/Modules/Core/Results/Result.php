<?php

declare(strict_types=1);

namespace Modules\Core\Results;

use InvalidArgumentException;
use LogicException;

final class Result
{
    private function __construct(
        private readonly bool $successful,
        private readonly mixed $value = null,
        private readonly ?Error $error = null,
    ) {
        if ($this->successful && $this->error !== null) {
            throw new InvalidArgumentException('Successful result cannot contain an error.');
        }

        if (! $this->successful && $this->error === null) {
            throw new InvalidArgumentException('Failed result must contain an error.');
        }
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

    public function valueOrFail(): mixed
    {
        if ($this->isFailure()) {
            throw new LogicException('Cannot read value from a failed result.');
        }

        return $this->value;
    }

    public function errorOrFail(): Error
    {
        if ($this->isSuccess() || $this->error === null) {
            throw new LogicException('Cannot read error from a successful result.');
        }

        return $this->error;
    }
}
