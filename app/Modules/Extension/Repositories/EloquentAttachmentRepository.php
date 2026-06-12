<?php

declare(strict_types=1);

namespace Modules\Extension\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Models\AttachmentModel;

final class EloquentAttachmentRepository implements AttachmentRepositoryInterface
{
    public function __construct(private readonly AttachmentModel $model) {}

    public function findAccessible(
        int|string $id,
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
    ): ?AttachmentModel {
        return $this->accessibleQuery($tenantId, $organizationUnitId, $userId)->find($id);
    }

    public function findPublicByUuid(string $uuid): ?AttachmentModel
    {
        return $this->model->newQuery()
            ->where('uuid', trim($uuid))
            ->where('visibility', 'public')
            ->first();
    }

    public function paginateAccessible(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        array $filters,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $query = $this->accessibleQuery($tenantId, $organizationUnitId, $userId);

        foreach (['attachable_type', 'attachable_id', 'category', 'visibility', 'source_module'] as $column) {
            if (array_key_exists($column, $filters) && $filters[$column] !== null && $filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }

        if (! ($filters['include_versions'] ?? false)) {
            $query->where('is_current', true);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $like = '%'.$search.'%';
                $scope->where('display_name', 'like', $like)
                    ->orWhere('original_file_name', 'like', $like)
                    ->orWhere('document_number', 'like', $like)
                    ->orWhere('source_reference', 'like', $like);
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function versionsAccessible(
        string $versionGroupUuid,
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
    ): array {
        return $this->accessibleQuery($tenantId, $organizationUnitId, $userId)
            ->where('version_group_uuid', $versionGroupUuid)
            ->orderByDesc('version_number')
            ->get()
            ->all();
    }

    public function create(array $attributes): AttachmentModel
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(AttachmentModel $attachment, array $attributes): AttachmentModel
    {
        $attachment->fill($attributes);
        $attachment->save();

        return $attachment->refresh();
    }

    public function delete(AttachmentModel $attachment): bool
    {
        return (bool) $attachment->delete();
    }

    public function nextVersionNumber(int $tenantId, string $versionGroupUuid): int
    {
        return ((int) $this->model->newQuery()
            ->withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('version_group_uuid', $versionGroupUuid)
            ->max('version_number')) + 1;
    }

    public function markVersionGroupNotCurrent(int $tenantId, string $versionGroupUuid, int $userId): void
    {
        $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('version_group_uuid', $versionGroupUuid)
            ->where('is_current', true)
            ->update([
                'is_current' => false,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    private function accessibleQuery(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
    ): Builder {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $scope) use ($organizationUnitId): void {
                $scope->where('visibility', 'public')
                    ->orWhereNull('organization_unit_id');

                if ($organizationUnitId !== null) {
                    $scope->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->where(function (Builder $scope) use ($userId): void {
                $scope->where('visibility', '!=', 'restricted');
                if ($userId !== null) {
                    $scope->orWhere('uploaded_by', $userId);
                }
            });
    }
}
