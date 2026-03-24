<?php

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Events\TenantCreated;

class CreateTenantService extends BaseService implements CreateTenantServiceInterface
{
    private TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->tenantRepository = $repository;
    }

    protected function handle(array $data): Tenant
    {
        $dto = TenantData::fromArray($data);

        $databaseConfig = DatabaseConfig::fromArray($dto->database_config ?? []);
        $mailConfig = !empty($dto->mail_config) ? MailConfig::fromArray($dto->mail_config) : null;
        $cacheConfig = !empty($dto->cache_config) ? CacheConfig::fromArray($dto->cache_config) : null;
        $queueConfig = !empty($dto->queue_config) ? QueueConfig::fromArray($dto->queue_config) : null;
        $featureFlags = new FeatureFlags($dto->feature_flags ?? []);
        $apiKeys = new ApiKeys($dto->api_keys ?? []);

        $tenant = new Tenant(
            name: $dto->name,
            domain: $dto->domain,
            databaseConfig: $databaseConfig,
            mailConfig: $mailConfig,
            cacheConfig: $cacheConfig,
            queueConfig: $queueConfig,
            featureFlags: $featureFlags,
            apiKeys: $apiKeys,
            active: $dto->active ?? true
        );

        $saved = $this->tenantRepository->save($tenant);
        $this->addEvent(new TenantCreated($saved));
        return $saved;
    }
}

