<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTO;

final readonly class CurrentTenantContext
{
    public function __construct(
        private DataRecord $tenant,
        private int $tenantId,
        private string $tenantCode,
        private string $tenantUuid,
        private ?string $isolationKey,
        private ?string $domain,
        private ?string $status,
        private bool $isActive,
        private ?string $applicationId,
        private string $source,
    ) {
    }

    public function tenant(): DataRecord
    {
        return $this->tenant;
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function tenantCode(): string
    {
        return $this->tenantCode;
    }

    public function tenantUuid(): string
    {
        return $this->tenantUuid;
    }

    public function isolationKey(): ?string
    {
        return $this->isolationKey;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function applicationId(): ?string
    {
        return $this->applicationId;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant' => $this->tenant->toArray(),
            'tenant_id' => $this->tenantId,
            'tenant_code' => $this->tenantCode,
            'tenant_uuid' => $this->tenantUuid,
            'isolation_key' => $this->isolationKey,
            'domain' => $this->domain,
            'status' => $this->status,
            'is_active' => $this->isActive,
            'application_id' => $this->applicationId,
            'source' => $this->source,
        ];
    }
}
