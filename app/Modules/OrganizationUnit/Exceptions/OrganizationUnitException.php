<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Exceptions;

use Modules\Core\Exceptions\DomainException;
use Modules\OrganizationUnit\Constants\OrganizationUnitErrorCode;

final class OrganizationUnitException extends DomainException
{
    /** @param array<string, scalar|array|null> $context */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $message): self
    {
        return new self(OrganizationUnitErrorCode::NOT_FOUND, $message);
    }

    public static function tenantNotFound(): self
    {
        return new self(OrganizationUnitErrorCode::TENANT_NOT_FOUND, 'Tenant not found.');
    }

    public static function planLimitReached(): self
    {
        return new self(
            OrganizationUnitErrorCode::PLAN_LIMIT_REACHED,
            'The tenant plan organization-unit limit has been reached.',
        );
    }

    public static function conflict(string $message): self
    {
        return new self(OrganizationUnitErrorCode::CONFLICT, $message);
    }

    public static function invalid(string $message): self
    {
        return new self(OrganizationUnitErrorCode::INVALID_VALUE, $message);
    }

    public static function versionConflict(string $message): self
    {
        return new self(OrganizationUnitErrorCode::VERSION_CONFLICT, $message);
    }

    /** @param array<string, scalar|array|null> $context */
    public static function lifecycleBlocked(string $message, array $context = []): self
    {
        return new self(OrganizationUnitErrorCode::LIFECYCLE_BLOCKED, $message, $context);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<string, scalar|array|null> */
    public function context(): array
    {
        return $this->context;
    }
}
