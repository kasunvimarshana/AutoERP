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
        private readonly string $idAttribute,
        private readonly string $tenantIdAttribute,
        private readonly string $codeAttribute,
        private readonly string $pathAttribute,
        private readonly string $nameAttribute,
        private readonly string $activeAttribute,
        private readonly string $applicationAttribute,
        private readonly string $sourceAttribute,
    ) {}

    public function current(): ?CurrentOrganizationUnitContext
    {
        $serialized = $this->request->attributes->get($this->requestAttribute);
        $serialized = is_array($serialized) ? $serialized : [];

        $organizationUnitId = $this->intOrNull(
            $serialized['organization_unit_id'] ?? $this->request->attributes->get($this->idAttribute),
        );
        $tenantId = $this->intOrNull(
            $serialized['tenant_id'] ?? $this->request->attributes->get($this->tenantIdAttribute),
        );
        $name = $this->stringOrNull(
            $serialized['organization_unit_name'] ?? $this->request->attributes->get($this->nameAttribute),
        );

        if ($organizationUnitId === null || $tenantId === null || $name === null) {
            return null;
        }

        $record = $serialized['organization_unit'] ?? null;
        $record = is_array($record)
            ? $record
            : [
                'id' => $organizationUnitId,
                'tenant_id' => $tenantId,
                'code' => $serialized['organization_unit_code']
                    ?? $this->request->attributes->get($this->codeAttribute),
                'path' => $serialized['organization_unit_path']
                    ?? $this->request->attributes->get($this->pathAttribute),
                'name' => $name,
                'is_active' => $serialized['is_active']
                    ?? $this->request->attributes->get($this->activeAttribute),
            ];

        return new CurrentOrganizationUnitContext(
            new DataRecord($record),
            $organizationUnitId,
            $tenantId,
            $this->stringOrNull(
                $serialized['organization_unit_code'] ?? $this->request->attributes->get($this->codeAttribute),
            ),
            $this->stringOrNull(
                $serialized['organization_unit_path'] ?? $this->request->attributes->get($this->pathAttribute),
            ),
            $name,
            $this->toBool($serialized['is_active'] ?? $this->request->attributes->get($this->activeAttribute)),
            $this->stringOrNull(
                $serialized['application_id'] ?? $this->request->attributes->get($this->applicationAttribute),
            ),
            $this->stringOrFallback($serialized['source'] ?? $this->request->attributes->get($this->sourceAttribute), 'resolved'),
        );
    }

    public function requireCurrent(): CurrentOrganizationUnitContext
    {
        $context = $this->current();
        if ($context === null) {
            throw new LogicException('Current organization unit context is not available on the active request.');
        }

        return $context;
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

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function stringOrFallback(mixed $value, string $fallback): string
    {
        return $this->stringOrNull($value) ?? $fallback;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }
}
