<?php

declare(strict_types=1);

namespace Modules\Organisation\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Organisation\Domain\Contracts\OrganisationRepositoryInterface;
use Modules\Organisation\Domain\Entities\Organisation;
use Modules\Organisation\Infrastructure\Models\OrganisationModel;

class OrganisationRepository extends BaseRepository implements OrganisationRepositoryInterface
{
    protected function model(): string
    {
        return OrganisationModel::class;
    }

    public function findById(int $id, int $tenantId): ?Organisation
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(string $code, int $tenantId): ?Organisation
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findChildren(int $parentId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get()
            ->map(fn (OrganisationModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findRoots(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (OrganisationModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (OrganisationModel $m) => $this->toDomain($m))->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(Organisation $organisation): Organisation
    {
        if ($organisation->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $organisation->tenantId)
                ->findOrFail($organisation->id);
        } else {
            $model = new OrganisationModel;
            $model->tenant_id = $organisation->tenantId;
        }

        $model->parent_id = $organisation->parentId;
        $model->type = $organisation->type;
        $model->name = $organisation->name;
        $model->code = $organisation->code;
        $model->description = $organisation->description;
        $model->status = $organisation->status;
        $model->meta = $organisation->meta;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(OrganisationModel $model): Organisation
    {
        return new Organisation(
            id: $model->id,
            tenantId: $model->tenant_id,
            parentId: $model->parent_id,
            type: $model->type,
            name: $model->name,
            code: $model->code,
            description: $model->description,
            status: $model->status,
            meta: $model->meta,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
