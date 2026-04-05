<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Configuration\Domain\Entities\Setting;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\SettingModel;

class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function __construct(private readonly SettingModel $model) {}

    private function toEntity(SettingModel $m): Setting
    {
        return new Setting($m->id, $m->tenant_id, $m->key, $m->value, $m->type, $m->description, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?Setting
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByKey(int $tenantId, string $key): ?Setting
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function set(int $tenantId, string $key, mixed $value, string $type = 'string', ?string $description = null): Setting
    {
        $m = $this->model->newQuery()->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value, 'type' => $type, 'description' => $description]
        );
        return $this->toEntity($m->fresh());
    }

    public function delete(int $tenantId, string $key): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->delete();
    }
}
