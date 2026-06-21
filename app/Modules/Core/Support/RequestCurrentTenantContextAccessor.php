<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\DataRecord;

final class RequestCurrentTenantContextAccessor implements CurrentTenantContextAccessorInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly string $requestAttribute,
    ) {}

    public function current(): ?CurrentTenantContext
    {
        $context = $this->request->attributes->get($this->requestAttribute);

        return $context instanceof CurrentTenantContext ? $context : null;
    }

    public function requireCurrent(): CurrentTenantContext
    {
        return $this->current()
            ?? throw new LogicException('Current tenant context is not available on the active request.');
    }

    public function currentTenant(): ?DataRecord
    {
        return $this->current()?->tenant();
    }

    public function currentTenantId(): ?int
    {
        return $this->current()?->tenantId();
    }

    public function currentTenantCode(): ?string
    {
        return $this->current()?->tenantCode();
    }

    public function currentTenantUuid(): ?string
    {
        return $this->current()?->tenantUuid();
    }

    public function currentTenantDomain(): ?string
    {
        return $this->current()?->domain();
    }

    public function currentApplicationId(): ?string
    {
        return $this->current()?->applicationId();
    }
}
