<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tenant\Domain\Entities\TenantSetting;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantSettingModel;

class EloquentTenantSettingRepository extends EloquentRepository implements TenantSettingRepositoryInterface
{
    public function __construct(TenantSettingModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TenantSettingModel $model): TenantSetting => $this->mapModelToDomainEntity($model));
    }

    public function findByTenantAndKey(int $tenantId, string $key): ?TenantSetting
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByTenant(int $tenantId, ?string $group = null, ?bool $isPublic = null): Collection
    {
        $query = $this->model->where('tenant_id', $tenantId);

        if ($group !== null && $group !== '') {
            $query->where('group', $group);
        }

        if ($isPublic !== null) {
            $query->where('is_public', $isPublic);
        }

        return $this->toDomainCollection($query->get());
    }

    public function save(TenantSetting $setting): TenantSetting
    {
        $data = [
            'tenant_id' => $setting->getTenantId(),
            'key' => $setting->getKey(),
            'value' => $setting->getValue(),
            'group' => $setting->getGroup(),
            'is_public' => $setting->isPublic(),
        ];

        if ($setting->getId()) {
            $model = $this->update($setting->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TenantSettingModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*']): ?TenantSetting
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TenantSettingModel $model): TenantSetting
    {
        return new TenantSetting(
            tenantId: $model->tenant_id,
            key: $model->key,
            value: $model->value,
            group: $model->group,
            isPublic: (bool) $model->is_public,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
