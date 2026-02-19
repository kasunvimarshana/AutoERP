<?php

declare(strict_types=1);

namespace Modules\Document\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Document\Exceptions\DocumentNotFoundException;
use Modules\Document\Models\Document;

/**
 * Document Repository
 *
 * Handles data access for documents
 */
class DocumentRepository extends BaseRepository
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }

    /**
     * Find document by ID
     *
     * @throws DocumentNotFoundException
     */
    public function findById(string $id): Document
    {
        $document = $this->model->with(['folder', 'owner', 'tags'])->find($id);

        if (! $document) {
            throw new DocumentNotFoundException("Document with ID {$id} not found");
        }

        return $document;
    }

    /**
     * Get documents by folder
     */
    public function getByFolder(string $folderId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('folder_id', $folderId)
            ->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Get documents by owner
     */
    public function getByOwner(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('owner_id', $userId)
            ->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Search documents
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $searchQuery = $this->model->where('is_latest_version', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('original_name', 'like', "%{$query}%");
            });

        return $this->applyFilters($searchQuery, $filters);
    }

    /**
     * Get documents by tag
     */
    public function getByTag(string $tagId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('tags', function ($q) use ($tagId) {
            $q->where('document_tags.id', $tagId);
        })->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Get documents by tags
     */
    public function getByTags(array $tagIds, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('document_tags.id', $tagIds);
        })->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Get documents by date range
     */
    public function getByDateRange(string $startDate, string $endDate, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate])
            ->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Get shared documents for user
     */
    public function getSharedWithUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('shares', function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        })->where('is_latest_version', true);

        return $this->applyFilters($query, $filters);
    }

    /**
     * Get recent documents
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->where('is_latest_version', true)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular documents
     */
    public function getPopular(int $limit = 10): Collection
    {
        return $this->model->where('is_latest_version', true)
            ->orderByDesc('download_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): LengthAwarePaginator
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['access_level'])) {
            $query->where('access_level', $filters['access_level']);
        }

        if (isset($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        if (isset($filters['extension'])) {
            $query->where('extension', $filters['extension']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $query->with(['folder', 'owner', 'tags']);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }
}
