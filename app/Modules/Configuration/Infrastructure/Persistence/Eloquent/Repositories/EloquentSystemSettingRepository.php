<?php

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Entities\SystemSetting;
use Modules\Configuration\Domain\RepositoryInterfaces\SystemSettingRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\SystemSettingModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentSystemSettingRepository extends EloquentRepository implements SystemSettingRepositoryInterface
{
    public function __construct(SystemSettingModel $model)
    {
        parent::__construct($model);
    }

    public function findByKey(int $tenantId, string $group, string $key): ?SystemSetting
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('group', $group)->where('key', $key)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByGroup(int $tenantId, string $group): array
    {
        return $this->model->where('tenant_id', $tenantId)->where('group', $group)->get()
            ->map(fn ($m) => $this->toEntity($m))->toArray();
    }

    public function upsert(int $tenantId, string $group, string $key, ?string $value, string $type): SystemSetting
    {
        $m = $this->model->updateOrCreate(
            ['tenant_id' => $tenantId, 'group' => $group, 'key' => $key],
            ['value' => $value, 'type' => $type]
        );
        return $this->toEntity($m);
    }

    public function create(array $data): SystemSetting
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(SystemSetting $setting, array $data): SystemSetting
    {
        $m = $this->model->findOrFail($setting->id);
        return $this->toEntity(parent::update($m, $data));
    }

    private function toEntity(object $m): SystemSetting
    {
        return new SystemSetting(
            id: $m->id,
            tenantId: $m->tenant_id,
            group: $m->group,
            key: $m->key,
            value: $m->value,
            type: $m->type,
            isEncrypted: (bool) $m->is_encrypted,
            isPublic: (bool) $m->is_public,
        );
    }
}
