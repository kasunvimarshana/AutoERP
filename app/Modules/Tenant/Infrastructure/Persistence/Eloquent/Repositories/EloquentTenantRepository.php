<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tenant\Domain\ValueObjects\ApiKeys;
use Modules\Tenant\Domain\ValueObjects\CacheConfig;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\Tenant\Domain\ValueObjects\FeatureFlags;
use Modules\Tenant\Domain\ValueObjects\MailConfig;
use Modules\Tenant\Domain\ValueObjects\QueueConfig;
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
            'slug' => $tenant->getSlug(),
            'domain' => $tenant->getDomain(),
            'logo_path' => $tenant->getLogoPath(),
            'database_config' => $tenant->getDatabaseConfig()->toArray(),
            'mail_config' => $tenant->getMailConfig()?->toArray(),
            'cache_config' => $tenant->getCacheConfig()?->toArray(),
            'queue_config' => $tenant->getQueueConfig()?->toArray(),
            'feature_flags' => $tenant->getFeatureFlags()->toArray(),
            'api_keys' => $tenant->getApiKeys()->toArray(),
            'settings' => $tenant->getSettings(),
            'plan' => $tenant->getPlan(),
            'tenant_plan_id' => $tenant->getTenantPlanId(),
            'status' => $tenant->getStatus(),
            'trial_ends_at' => $tenant->getTrialEndsAt(),
            'subscription_ends_at' => $tenant->getSubscriptionEndsAt(),
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

    private function mapModelToDomainEntity(TenantModel $model): Tenant
    {
        return new Tenant(
            name: $model->name,
            slug: $model->slug,
            databaseConfig: DatabaseConfig::fromArray($model->database_config ?? []),
            domain: $model->domain,
            logoPath: $model->logo_path,
            mailConfig: ! empty($model->mail_config) ? MailConfig::fromArray($model->mail_config) : null,
            cacheConfig: ! empty($model->cache_config) ? CacheConfig::fromArray($model->cache_config) : null,
            queueConfig: ! empty($model->queue_config) ? QueueConfig::fromArray($model->queue_config) : null,
            featureFlags: new FeatureFlags($model->feature_flags ?? []),
            apiKeys: new ApiKeys($model->api_keys ?? []),
            settings: $model->settings,
            plan: $model->plan ?? 'free',
            tenantPlanId: $model->tenant_plan_id,
            status: $model->status ?? 'active',
            trialEndsAt: $model->trial_ends_at,
            subscriptionEndsAt: $model->subscription_ends_at,
            active: (bool) $model->active,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
