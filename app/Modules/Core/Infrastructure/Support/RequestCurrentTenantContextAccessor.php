<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Support;

use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\DTO\CurrentTenantContext;
use Modules\Core\Application\DTO\DataRecord;

final class RequestCurrentTenantContextAccessor implements CurrentTenantContextAccessorInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly string $requestAttribute,
        private readonly string $idAttribute,
        private readonly string $codeAttribute,
        private readonly string $uuidAttribute,
        private readonly string $isolationKeyAttribute,
        private readonly string $domainAttribute,
        private readonly string $statusAttribute,
        private readonly string $activeAttribute,
        private readonly string $applicationAttribute,
        private readonly string $sourceAttribute,
    ) {
    }

    public function current(): ?CurrentTenantContext
    {
        $serialized = $this->request->attributes->get($this->requestAttribute);
        $serialized = is_array($serialized) ? $serialized : [];

        $tenantId = $this->intOrNull($serialized['tenant_id'] ?? $this->request->attributes->get($this->idAttribute));
        $tenantCode = $this->stringOrNull(
            $serialized['tenant_code'] ?? $this->request->attributes->get($this->codeAttribute),
        );
        $tenantUuid = $this->stringOrNull(
            $serialized['tenant_uuid'] ?? $this->request->attributes->get($this->uuidAttribute),
        );

        if ($tenantId === null || $tenantCode === null || $tenantUuid === null) {
            return null;
        }

        $tenant = $serialized['tenant'] ?? null;
        $tenant = is_array($tenant) ? $tenant : [
            'id' => $tenantId,
            'code' => $tenantCode,
            'uuid' => $tenantUuid,
            'isolation_key' => $serialized['isolation_key']
                ?? $this->request->attributes->get($this->isolationKeyAttribute),
            'status' => $serialized['status'] ?? $this->request->attributes->get($this->statusAttribute),
            'is_active' => $this->boolValue($serialized['is_active'] ?? $this->request->attributes->get($this->activeAttribute)),
        ];

        return new CurrentTenantContext(
            new DataRecord($tenant),
            $tenantId,
            $tenantCode,
            $tenantUuid,
            $this->stringOrNull(
                $serialized['isolation_key'] ?? $this->request->attributes->get($this->isolationKeyAttribute),
            ),
            $this->stringOrNull($serialized['domain'] ?? $this->request->attributes->get($this->domainAttribute)),
            $this->stringOrNull($serialized['status'] ?? $this->request->attributes->get($this->statusAttribute)),
            $this->boolValue($serialized['is_active'] ?? $this->request->attributes->get($this->activeAttribute)),
            $this->stringOrNull(
                $serialized['application_id'] ?? $this->request->attributes->get($this->applicationAttribute),
            ),
            $this->stringOrFallback($serialized['source'] ?? $this->request->attributes->get($this->sourceAttribute), 'resolved'),
        );
    }

    public function requireCurrent(): CurrentTenantContext
    {
        $context = $this->current();
        if ($context === null) {
            throw new LogicException('Current tenant context is not available on the active request.');
        }

        return $context;
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

        return (int) $value;
    }

    private function boolValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }
}
