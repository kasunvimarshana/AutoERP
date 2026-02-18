<?php

namespace App\Repositories;

use App\Models\MetadataEntity;
use Illuminate\Database\Eloquent\Collection;

class MetadataEntityRepository extends BaseRepository
{
    protected function model(): string
    {
        return MetadataEntity::class;
    }

    /**
     * Find entity by name.
     */
    public function findByName(string $name): ?MetadataEntity
    {
        return $this->model
            ->where('name', $name)
            ->first();
    }

    /**
     * Get entities by module.
     */
    public function getByModule(string $module): Collection
    {
        return $this->model
            ->where('module', $module)
            ->where('is_active', true)
            ->with(['fields' => function ($query) {
                $query->orderBy('order');
            }])
            ->get();
    }

    /**
     * Get all active entities with fields.
     */
    public function getAllActiveWithFields(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->with(['fields' => function ($query) {
                $query->orderBy('order');
            }])
            ->orderBy('module')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get entity with fields and workflows.
     */
    public function getWithRelations(int $id): ?MetadataEntity
    {
        return $this->model
            ->with(['fields', 'workflows'])
            ->find($id);
    }
}
