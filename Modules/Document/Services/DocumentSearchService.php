<?php

declare(strict_types=1);

namespace Modules\Document\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Document\Models\DocumentSearchHistory;
use Modules\Document\Repositories\DocumentRepository;
use Modules\Document\Repositories\DocumentTagRepository;

/**
 * DocumentSearchService
 *
 * Provides search functionality for documents
 */
class DocumentSearchService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentTagRepository $tagRepository,
    ) {}

    /**
     * Full-text search across documents
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $result = $this->documentRepository->search($query, $filters);
        
        // Record search history
        $this->recordSearch($query, $filters, $result->total());
        
        return $result;
    }

    /**
     * Search by tags
     */
    public function searchByTags(array $tagNames, array $filters = []): LengthAwarePaginator
    {
        // Get tag IDs from names
        $tags = $this->tagRepository->model
            ->whereIn('name', $tagNames)
            ->pluck('id')
            ->toArray();

        if (empty($tags)) {
            return new LengthAwarePaginator([], 0, $filters['per_page'] ?? 15);
        }

        return $this->documentRepository->getByTags($tags, $filters);
    }

    /**
     * Search by tag IDs
     */
    public function searchByTagIds(array $tagIds, array $filters = []): LengthAwarePaginator
    {
        return $this->documentRepository->getByTags($tagIds, $filters);
    }

    /**
     * Search by date range
     */
    public function searchByDateRange(string $startDate, string $endDate, array $filters = []): LengthAwarePaginator
    {
        return $this->documentRepository->getByDateRange($startDate, $endDate, $filters);
    }

    /**
     * Search by folder
     */
    public function searchByFolder(string $folderId, array $filters = []): LengthAwarePaginator
    {
        return $this->documentRepository->getByFolder($folderId, $filters);
    }

    /**
     * Search by owner
     */
    public function searchByOwner(string $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->documentRepository->getByOwner($userId, $filters);
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $query = $this->documentRepository->model->query()->where('is_latest_version', true);

        // Text search
        if (! empty($criteria['query'])) {
            $searchQuery = $criteria['query'];
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('description', 'like', "%{$searchQuery}%")
                    ->orWhere('original_name', 'like', "%{$searchQuery}%");
            });
        }

        // Folder filter
        if (! empty($criteria['folder_id'])) {
            $query->where('folder_id', $criteria['folder_id']);
        }

        // Owner filter
        if (! empty($criteria['owner_id'])) {
            $query->where('owner_id', $criteria['owner_id']);
        }

        // Status filter
        if (! empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        // Type filter
        if (! empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        // Access level filter
        if (! empty($criteria['access_level'])) {
            $query->where('access_level', $criteria['access_level']);
        }

        // MIME type filter
        if (! empty($criteria['mime_type'])) {
            $query->where('mime_type', $criteria['mime_type']);
        }

        // Extension filter
        if (! empty($criteria['extension'])) {
            $query->where('extension', $criteria['extension']);
        }

        // Size range filter
        if (! empty($criteria['min_size'])) {
            $query->where('size', '>=', $criteria['min_size']);
        }
        if (! empty($criteria['max_size'])) {
            $query->where('size', '<=', $criteria['max_size']);
        }

        // Date range filter
        if (! empty($criteria['from_date'])) {
            $query->where('created_at', '>=', $criteria['from_date']);
        }
        if (! empty($criteria['to_date'])) {
            $query->where('created_at', '<=', $criteria['to_date']);
        }

        // Tag filter
        if (! empty($criteria['tags'])) {
            $tagIds = is_array($criteria['tags']) ? $criteria['tags'] : [$criteria['tags']];
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('document_tags.id', $tagIds);
            });
        }

        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Load relationships
        $query->with(['folder', 'owner', 'tags']);

        // Paginate
        $perPage = $criteria['per_page'] ?? 15;

        $result = $query->paginate($perPage);
        
        // Record search history for advanced searches
        if (! empty($criteria['query'])) {
            $this->recordSearch($criteria['query'], $criteria, $result->total());
        }

        return $result;
    }

    /**
     * Get suggested tags based on query
     */
    public function suggestTags(string $query): array
    {
        return $this->tagRepository->search($query)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get recent searches (could be expanded with a search history table)
     */
    public function getRecentSearches(int $limit = 10): array
    {
        $userId = Auth::id();
        
        if (! $userId) {
            return [];
        }

        return DocumentSearchHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                return [
                    'query' => $history->query,
                    'filters' => $history->filters,
                    'results_count' => $history->results_count,
                    'searched_at' => $history->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Record search in history
     */
    private function recordSearch(string $query, array $filters, int $resultsCount): void
    {
        $userId = Auth::id();
        
        if (! $userId) {
            return;
        }

        try {
            DocumentSearchHistory::create([
                'user_id' => $userId,
                'query' => $query,
                'filters' => $filters,
                'results_count' => $resultsCount,
            ]);
        } catch (\Throwable $e) {
            // Log the error for debugging but don't fail the search
            Log::warning('Failed to record document search history', [
                'user_id' => $userId,
                'query' => $query,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }
    }

    /**
     * Clear user's search history
     */
    public function clearSearchHistory(?string $userId = null): int
    {
        $userId = $userId ?? Auth::id();
        
        if (! $userId) {
            return 0;
        }

        return DocumentSearchHistory::where('user_id', $userId)->delete();
    }

    /**
     * Get popular searches across all users (for suggestions)
     */
    public function getPopularSearches(int $limit = 10, int $daysBack = 30): array
    {
        return DocumentSearchHistory::where('created_at', '>=', now()->subDays($daysBack))
            ->selectRaw('query, COUNT(*) as search_count, AVG(results_count) as avg_results')
            ->groupBy('query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'query' => $item->query,
                    'search_count' => $item->search_count,
                    'avg_results' => round($item->avg_results, 2),
                ];
            })
            ->toArray();
    }
}
