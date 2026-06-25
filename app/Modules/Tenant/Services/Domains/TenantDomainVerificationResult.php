<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

final readonly class TenantDomainVerificationResult
{
    private function __construct(
        public bool $verified,
        public ?string $errorCode,
        public ?string $message,
    ) {}

    public static function verified(): self
    {
        return new self(true, null, null);
    }

    public static function failed(string $errorCode, string $message): self
    {
        return new self(false, trim($errorCode), trim($message));
    }
}
