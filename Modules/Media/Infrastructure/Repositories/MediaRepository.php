<?php

namespace Modules\Media\Infrastructure\Repositories;

use Modules\Media\Domain\Contracts\MediaRepositoryInterface;
use Modules\Media\Infrastructure\Models\MediaModel;

class MediaRepository implements MediaRepositoryInterface
{
    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = MediaModel::where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if (! empty($filters['folder'])) {
            $query->where('folder', $filters['folder']);
        }

        if (! empty($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
            if (! empty($filters['model_id'])) {
                $query->where('model_id', $filters['model_id']);
            }
        }

        return $query->paginate($perPage);
    }

    public function findById(string $id): ?object
    {
        return MediaModel::find($id);
    }

    public function create(array $data): object
    {
        return MediaModel::create($data);
    }

    public function delete(string $id): void
    {
        MediaModel::findOrFail($id)->delete();
    }
}
