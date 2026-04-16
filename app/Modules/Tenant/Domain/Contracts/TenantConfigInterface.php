<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Contracts;

interface TenantConfigInterface
{
    public function getDatabaseConfig(): array;

    public function getMailConfig(): ?array;

    public function getCacheConfig(): ?array;

    public function getQueueConfig(): ?array;

    public function getFeatureFlags(): array;

    public function getApiKeys(): array;

    // Add other configuration groups as needed
}
