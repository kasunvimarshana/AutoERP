<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\ValueObjects\ApiKeys;
use Modules\Core\Domain\ValueObjects\CacheConfig;
use Modules\Core\Domain\ValueObjects\DatabaseConfig;
use Modules\Core\Domain\ValueObjects\FeatureFlags;
use Modules\Core\Domain\ValueObjects\MailConfig;
use Modules\Core\Domain\ValueObjects\QueueConfig;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class EloquentTenantRepository extends EloquentRepository implements TenantRepositoryInterface
{
    public function __construct(TenantModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TenantModel $model): Tenant => $this->mapModelToDomainEntity($model));
    }

    public function findByDomain(string $domain): ?Tenant
    {
        $model = $this->model->where('domain', $domain)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(Tenant $tenant): Tenant
    {
        $data = [
            'name' => $tenant->getName(),
            'domain' => $tenant->getDomain(),
            'logo_path' => $tenant->getLogoPath(),
            'database_config' => $tenant->getDatabaseConfig()->toArray(),
            'mail_config' => $tenant->getMailConfig()?->toArray(),
            'cache_config' => $tenant->getCacheConfig()?->toArray(),
            'queue_config' => $tenant->getQueueConfig()?->toArray(),
            'feature_flags' => $tenant->getFeatureFlags()->toArray(),
            'api_keys' => $tenant->getApiKeys()->toArray(),
            'active' => $tenant->isActive(),
        ];

        if ($tenant->getId()) {
            $model = $this->update($tenant->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TenantModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    public function delete($id): bool
    {
        return parent::delete($id);
    }

    /**
     * Find a tenant by ID and convert to domain entity.
     *
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*']): ?Tenant
    {
        return parent::find($id, $columns);
    }

    /**
     * Paginate tenants and convert each row to a domain entity.
     *
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function mapModelToDomainEntity(TenantModel $model): Tenant
    {
        return new Tenant(
            name: $model->name,
            databaseConfig: DatabaseConfig::fromArray($model->database_config ?? []),
            domain: $model->domain,
            logoPath: $model->logo_path,
            mailConfig: ! empty($model->mail_config) ? MailConfig::fromArray($model->mail_config) : null,
            cacheConfig: ! empty($model->cache_config) ? CacheConfig::fromArray($model->cache_config) : null,
            queueConfig: ! empty($model->queue_config) ? QueueConfig::fromArray($model->queue_config) : null,
            featureFlags: new FeatureFlags($model->feature_flags ?? []),
            apiKeys: new ApiKeys($model->api_keys ?? []),
            active: (bool) $model->active,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
