<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantDomainOwnershipVerifierInterface
{
    public function isVerified(string $domain, string $expectedTokenHash): bool;
}
