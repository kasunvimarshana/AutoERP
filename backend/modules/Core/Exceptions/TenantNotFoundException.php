<?php

namespace Modules\Core\Exceptions;

/**
 * Thrown when a tenant cannot be found
 */
class TenantNotFoundException extends DomainException
{
    protected int $statusCode = 404;

    public static function withIdentifier(string $identifier): self
    {
        return new self(
            "Tenant not found with identifier: {$identifier}",
            ['identifier' => $identifier]
        );
    }

    public static function withId(int $id): self
    {
        return new self(
            "Tenant not found with ID: {$id}",
            ['tenant_id' => $id]
        );
    }
}
