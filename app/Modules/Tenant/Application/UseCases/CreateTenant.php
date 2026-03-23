<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\MailConfig;
use Modules\Tenant\Domain\ValueObjects\CacheConfig;
use Modules\Tenant\Domain\ValueObjects\QueueConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;
use Modules\Tenant\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Tenant\TenantCreated;

class CreateTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(TenantData $data): Tenant
    {
        $databaseConfig = new DatabaseConfig($data->database_config);
        $mailConfig = $data->mail_config ? new MailConfig($data->mail_config) : null;
        $cacheConfig = $data->cache_config ? new CacheConfig($data->cache_config) : null;
        $queueConfig = $data->queue_config ? new QueueConfig($data->queue_config) : null;
        $featureFlags = new FeatureFlags($data->feature_flags);
        $apiKeys = new ApiKeys($data->api_keys);

        $tenant = new Tenant(
            name: $data->name,
            domain: $data->domain,
            databaseConfig: $databaseConfig,
            mailConfig: $mailConfig,
            cacheConfig: $cacheConfig,
            queueConfig: $queueConfig,
            featureFlags: $featureFlags,
            apiKeys: $apiKeys,
            active: $data->active,
        );

        $saved = $this->tenantRepo->save($tenant);

        event(new TenantCreated($saved));

        return $saved;
    }
}
