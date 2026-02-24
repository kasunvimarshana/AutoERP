<?php
namespace Modules\Setting\Infrastructure\Repositories;
use Modules\Setting\Domain\Contracts\SettingRepositoryInterface;
use Modules\Setting\Infrastructure\Models\SettingModel;
class SettingRepository implements SettingRepositoryInterface
{
    public function __construct(private SettingModel $model) {}
    public function get(string $key, string $tenantId, mixed $default = null): mixed
    {
        $setting = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        return $setting ? $setting->value : $default;
    }
    public function set(string $key, mixed $value, string $tenantId, string $group, string $type = 'string'): void
    {
        $this->model->newQuery()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            [
                'group' => $group,
                'value' => $value,
                'type' => $type,
                'version' => \Illuminate\Support\Facades\DB::raw('version + 1'),
            ]
        );
    }
    public function getGroup(string $group, string $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
    public function getAll(string $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->groupBy('group')
            ->map(fn ($group) => $group->pluck('value', 'key'))
            ->toArray();
    }
}
