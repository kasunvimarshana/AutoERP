<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

interface TenantContextResolverInterface
{
    public function resolveTenantId(): ?int;
}
