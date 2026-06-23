<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

use Modules\Tenant\Services\Domains\TenantDomainVerificationResult;

interface TenantDomainOwnershipVerifierInterface
{
    public function verify(string $domain, string $expectedTokenHash): TenantDomainVerificationResult;
}
