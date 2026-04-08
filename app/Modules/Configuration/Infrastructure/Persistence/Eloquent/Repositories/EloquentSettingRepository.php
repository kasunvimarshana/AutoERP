<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Configuration\Domain\Entities\Setting;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\SettingModel;

class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Setting
    {
        $model = SettingModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByKey(string $tenantId, string $key): ?Setting
    {
        $model = SettingModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return SettingModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(SettingModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByGroup(string $tenantId, string $group): array
    {
        return SettingModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('group', $group)
            ->get()
            ->map(fn(SettingModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(Setting $setting): void
    {
        /** @var SettingModel $model */
        $model = SettingModel::withoutGlobalScopes()->findOrNew($setting->id);

        $model->fill([
            'tenant_id'   => $setting->tenantId,
            'key'         => $setting->key,
            'value'       => $setting->value,
            'group'       => $setting->group,
            'type'        => $setting->type,
            'is_public'   => $setting->isPublic,
            'description' => $setting->description,
        ]);

        if (! $model->exists) {
            $model->id = $setting->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        SettingModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(SettingModel $model): Setting
    {
        return new Setting(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            key: (string) $model->key,
            value: (string) ($model->value ?? ''),
            group: (string) ($model->group ?? 'general'),
            type: (string) ($model->type ?? 'string'),
            isPublic: (bool) $model->is_public,
            description: $model->description !== null ? (string) $model->description : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
