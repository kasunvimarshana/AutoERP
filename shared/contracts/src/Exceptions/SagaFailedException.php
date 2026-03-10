<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Exceptions;

use RuntimeException;

/**
 * SagaFailedException
 *
 * Thrown when a saga cannot complete all steps and compensation has been
 * (or is being) triggered.
 */
class SagaFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $sagaId,
        public readonly string $failedStep,
        public readonly array  $context,
        string                 $message  = '',
        int                    $code     = 0,
        ?\Throwable            $previous = null,
    ) {
        parent::__construct(
            $message ?: "Saga [{$sagaId}] failed at step [{$failedStep}].",
            $code,
            $previous
        );
    }
}
