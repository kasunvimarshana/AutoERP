<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;

class UpdateTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(int $id, TenantData $data): Tenant
    {
        $tenant = $this->tenantRepo->find($id);
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }

        $tenant->update(
            name: $data->name,
            domain: $data->domain,
            databaseConfig: DatabaseConfig::fromArray($data->database_config ?? []),
            mailConfig: !empty($data->mail_config) ? MailConfig::fromArray($data->mail_config) : null,
            cacheConfig: !empty($data->cache_config) ? CacheConfig::fromArray($data->cache_config) : null,
            queueConfig: !empty($data->queue_config) ? QueueConfig::fromArray($data->queue_config) : null,
            featureFlags: new FeatureFlags($data->feature_flags ?? []),
            apiKeys: new ApiKeys($data->api_keys ?? []),
            active: $data->active ?? true,
        );

        $saved = $this->tenantRepo->save($tenant);
        event(new TenantUpdated($saved));
        return $saved;
    }
}
