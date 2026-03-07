<?php

namespace App\Saga;

/**
 * Immutable value object returned by every saga step execution or compensation.
 */
final class SagaStepResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly array  $data    = [],
        public readonly string $error   = '',
    ) {}

    public static function success(array $data = []): self
    {
        return new self(true, $data);
    }

    public static function failure(string $error, array $data = []): self
    {
        return new self(false, $data, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }
}
