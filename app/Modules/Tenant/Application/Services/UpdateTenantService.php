<?php

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\MailConfig;
use Modules\Tenant\Domain\ValueObjects\CacheConfig;
use Modules\Tenant\Domain\ValueObjects\QueueConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;
use Modules\Tenant\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Domain\Events\TenantUpdated;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;

class UpdateTenantService extends BaseService implements UpdateTenantServiceInterface
{
    private TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->tenantRepository = $repository;
    }

    protected function handle(array $data): Tenant
    {
        $id = $data['id'];
        $dto = TenantData::fromArray($data);

        $tenant = $this->tenantRepository->find($id);
        if (!$tenant) {
            throw new TenantNotFoundException($id);
        }

        $this->tenantRepository->update($id, [
            'name'            => $dto->name,
            'domain'          => $dto->domain,
            'database_config' => $dto->database_config ?? null,
            'mail_config'     => $dto->mail_config ?? null,
            'cache_config'    => $dto->cache_config ?? null,
            'queue_config'    => $dto->queue_config ?? null,
            'feature_flags'   => $dto->feature_flags ?? [],
            'api_keys'        => $dto->api_keys ?? [],
            'active'          => $dto->active ?? true,
        ]);

        $saved = $this->tenantRepository->find($id);
        $this->addEvent(new TenantUpdated($saved));
        return $saved;
    }
}
