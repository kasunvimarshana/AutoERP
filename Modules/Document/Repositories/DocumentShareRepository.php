<?php

declare(strict_types=1);

namespace Modules\Document\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Document\Enums\PermissionType;
use Modules\Document\Models\DocumentShare;

/**
 * DocumentShare Repository
 *
 * Handles data access for document shares
 */
class DocumentShareRepository extends BaseRepository
{
    public function __construct(DocumentShare $model)
    {
        parent::__construct($model);
    }

    /**
     * Get shares for document
     */
    public function getByDocument(string $documentId): Collection
    {
        return $this->model->where('document_id', $documentId)
            ->with('user')
            ->get();
    }

    /**
     * Get active shares for document
     */
    public function getActiveByDocument(string $documentId): Collection
    {
        return $this->model->where('document_id', $documentId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('user')
            ->get();
    }

    /**
     * Get share for user and document
     */
    public function getByUserAndDocument(string $userId, string $documentId): ?DocumentShare
    {
        return $this->model->where('user_id', $userId)
            ->where('document_id', $documentId)
            ->first();
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $userId, string $documentId, PermissionType $permission): bool
    {
        $share = $this->getByUserAndDocument($userId, $documentId);

        return $share && $share->hasPermission($permission);
    }

    /**
     * Get expired shares
     */
    public function getExpired(): Collection
    {
        return $this->model->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Delete expired shares
     */
    public function deleteExpired(): int
    {
        return $this->model->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
