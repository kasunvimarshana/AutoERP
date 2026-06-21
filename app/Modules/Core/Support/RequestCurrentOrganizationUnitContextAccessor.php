<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\DTOs\CurrentOrganizationUnitContext;
use Modules\Core\DTOs\DataRecord;

final class RequestCurrentOrganizationUnitContextAccessor implements CurrentOrganizationUnitContextAccessorInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly string $requestAttribute,
    ) {}

    public function current(): ?CurrentOrganizationUnitContext
    {
        $context = $this->request->attributes->get($this->requestAttribute);

        return $context instanceof CurrentOrganizationUnitContext ? $context : null;
    }

    public function requireCurrent(): CurrentOrganizationUnitContext
    {
        return $this->current()
            ?? throw new LogicException('Current organization unit context is not available on the active request.');
    }

    public function currentOrganizationUnit(): ?DataRecord
    {
        return $this->current()?->organizationUnit();
    }

    public function currentOrganizationUnitId(): ?int
    {
        return $this->current()?->organizationUnitId();
    }

    public function currentOrganizationUnitCode(): ?string
    {
        return $this->current()?->code();
    }

    public function currentOrganizationUnitPath(): ?string
    {
        return $this->current()?->path();
    }

    public function currentOrganizationUnitName(): ?string
    {
        return $this->current()?->name();
    }

    public function currentTenantId(): ?int
    {
        return $this->current()?->tenantId();
    }

    public function currentApplicationId(): ?string
    {
        return $this->current()?->applicationId();
    }
}
