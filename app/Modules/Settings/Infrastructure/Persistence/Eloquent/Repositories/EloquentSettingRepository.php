<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Settings\Domain\Entities\Setting;
use Modules\Settings\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Settings\Infrastructure\Persistence\Eloquent\Models\SettingModel;

class EloquentSettingRepository extends EloquentRepository implements SettingRepositoryInterface
{
    public function __construct(SettingModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SettingModel $m): Setting => $this->mapModelToDomainEntity($m));
    }

    public function save(Setting $setting): Setting
    {
        $savedModel = null;
        DB::transaction(function () use ($setting, &$savedModel) {
            $data = [
                'tenant_id'     => $setting->getTenantId(),
                'group_key'     => $setting->getGroupKey(),
                'setting_key'   => $setting->getSettingKey(),
                'setting_type'  => $setting->getSettingType(),
                'value'         => $setting->getRawValue() !== null ? (string) $setting->getRawValue() : null,
                'default_value' => $setting->getDefaultValue() !== null ? (string) $setting->getDefaultValue() : null,
                'label'         => $setting->getLabel(),
                'description'   => $setting->getDescription(),
                'is_system'     => $setting->isSystem(),
                'is_editable'   => $setting->isEditable(),
                'metadata'      => $setting->getMetadata()->toArray(),
            ];
            if ($setting->getId()) {
                $savedModel = $this->update($setting->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof SettingModel) {
            throw new \RuntimeException('Failed to save Setting.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByKey(int $tenantId, string $groupKey, string $settingKey): ?Setting
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('group_key', $groupKey)
            ->where('setting_key', $settingKey)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByGroup(int $tenantId, string $groupKey): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('group_key', $groupKey)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function list(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        foreach ($filters as $field => $value) {
            if ($value !== null) {
                $query->where($field, $value);
            }
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function delete(int $id): bool
    {
        $model = $this->model->find($id);
        if ($model) {
            return (bool) $model->delete();
        }

        return false;
    }

    private function mapModelToDomainEntity(SettingModel $model): Setting
    {
        return new Setting(
            tenantId:     $model->tenant_id,
            groupKey:     $model->group_key,
            settingKey:   $model->setting_key,
            label:        $model->label,
            value:        $model->value,
            defaultValue: $model->default_value,
            settingType:  $model->setting_type,
            description:  $model->description,
            isSystem:     (bool) $model->is_system,
            isEditable:   (bool) $model->is_editable,
            metadata:     isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:           $model->id,
            createdAt:    $model->created_at,
            updatedAt:    $model->updated_at,
        );
    }
}
