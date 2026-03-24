<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class CreateTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(TenantData $data): Tenant
    {
        $databaseConfig = DatabaseConfig::fromArray($data->database_config ?? []);
        $mailConfig = ! empty($data->mail_config) ? MailConfig::fromArray($data->mail_config) : null;
        $cacheConfig = ! empty($data->cache_config) ? CacheConfig::fromArray($data->cache_config) : null;
        $queueConfig = ! empty($data->queue_config) ? QueueConfig::fromArray($data->queue_config) : null;
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
