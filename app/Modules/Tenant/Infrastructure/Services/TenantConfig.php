<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Services;

use Modules\Tenant\Domain\Contracts\TenantConfigInterface;

class TenantConfig implements TenantConfigInterface
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getDatabaseConfig(): array
    {
        return $this->data['database_config'] ?? [];
    }

    public function getMailConfig(): ?array
    {
        return $this->data['mail_config'] ?? null;
    }

    public function getCacheConfig(): ?array
    {
        return $this->data['cache_config'] ?? null;
    }

    public function getQueueConfig(): ?array
    {
        return $this->data['queue_config'] ?? null;
    }

    public function getFeatureFlags(): array
    {
        return $this->data['feature_flags'] ?? [];
    }

    public function getApiKeys(): array
    {
        return $this->data['api_keys'] ?? [];
    }
}
