<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use RuntimeException;

final class TenantOnboardingOperationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $safeMessage,
        public readonly ?string $step = null,
        public readonly ?string $correlationId = null,
        /** @var array<string, mixed> */
        public readonly array $context = [],
    ) {
        parent::__construct($safeMessage);
    }
}
