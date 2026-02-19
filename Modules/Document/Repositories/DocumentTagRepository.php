<?php

declare(strict_types=1);

namespace Modules\Document\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Document\Models\DocumentTag;

/**
 * DocumentTag Repository
 *
 * Handles data access for document tags
 */
class DocumentTagRepository extends BaseRepository
{
    public function __construct(DocumentTag $model)
    {
        parent::__construct($model);
    }

    /**
     * Find tag by name
     */
    public function findByName(string $name): ?DocumentTag
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Get or create tag
     */
    public function getOrCreate(string $name, ?string $color = null): DocumentTag
    {
        $tag = $this->findByName($name);

        if (! $tag) {
            $tag = $this->create([
                'tenant_id' => auth()->user()?->tenant_id,
                'name' => $name,
                'color' => $color ?? $this->generateRandomColor(),
            ]);
        }

        return $tag;
    }

    /**
     * Get popular tags
     */
    public function getPopular(int $limit = 10): Collection
    {
        return $this->model->withCount('documents')
            ->orderByDesc('documents_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Search tags
     */
    public function search(string $query): Collection
    {
        return $this->model->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Generate random color for tag
     */
    private function generateRandomColor(): string
    {
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'];

        return $colors[array_rand($colors)];
    }
}
