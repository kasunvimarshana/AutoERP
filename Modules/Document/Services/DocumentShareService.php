<?php

declare(strict_types=1);

namespace Modules\Document\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Document\Enums\PermissionType;
use Modules\Document\Events\DocumentShared;
use Modules\Document\Exceptions\InsufficientPermissionException;
use Modules\Document\Models\Document;
use Modules\Document\Models\DocumentShare;
use Modules\Document\Repositories\DocumentRepository;
use Modules\Document\Repositories\DocumentShareRepository;

/**
 * DocumentShareService
 *
 * Manages document sharing and permissions
 */
class DocumentShareService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentShareRepository $shareRepository,
    ) {}

    /**
     * Share document with user
     */
    public function share(
        string $documentId,
        string $userId,
        PermissionType $permissionType,
        ?\DateTimeInterface $expiresAt = null
    ): DocumentShare {
        return TransactionHelper::execute(function () use ($documentId, $userId, $permissionType, $expiresAt) {
            $document = $this->documentRepository->findById($documentId);

            // Check if share already exists
            $existingShare = $this->shareRepository->getByUserAndDocument($userId, $documentId);

            if ($existingShare) {
                // Update existing share
                $existingShare->update([
                    'permission_type' => $permissionType,
                    'expires_at' => $expiresAt,
                ]);

                return $existingShare->fresh();
            }

            // Create new share
            $share = $this->shareRepository->create([
                'document_id' => $documentId,
                'user_id' => $userId,
                'permission_type' => $permissionType,
                'expires_at' => $expiresAt,
            ]);

            event(new DocumentShared($share));

            return $share;
        });
    }

    /**
     * Bulk share document with multiple users
     */
    public function bulkShare(
        string $documentId,
        array $userIds,
        PermissionType $permissionType,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $shares = [];

        foreach ($userIds as $userId) {
            $shares[] = $this->share($documentId, $userId, $permissionType, $expiresAt);
        }

        return $shares;
    }

    /**
     * Revoke share
     */
    public function revoke(string $shareId): bool
    {
        return TransactionHelper::execute(function () use ($shareId) {
            $share = $this->shareRepository->findOrFail($shareId);

            return $share->delete();
        });
    }

    /**
     * Revoke user's access to document
     */
    public function revokeUser(string $documentId, string $userId): bool
    {
        return TransactionHelper::execute(function () use ($documentId, $userId) {
            $share = $this->shareRepository->getByUserAndDocument($userId, $documentId);

            if (! $share) {
                return false;
            }

            return $share->delete();
        });
    }

    /**
     * Check if user has permission
     */
    public function checkPermission(string $documentId, string $userId, PermissionType $permission): bool
    {
        $document = $this->documentRepository->findById($documentId);

        // Owner has all permissions
        if ($document->owner_id === $userId) {
            return true;
        }

        // Public documents have view permission for everyone
        if ($document->isPublic() && $permission === PermissionType::VIEW) {
            return true;
        }

        // Check share permissions
        return $this->shareRepository->hasPermission($userId, $documentId, $permission);
    }

    /**
     * Ensure user has permission or throw exception
     */
    public function ensurePermission(string $documentId, string $userId, PermissionType $permission): void
    {
        if (! $this->checkPermission($documentId, $userId, $permission)) {
            throw new InsufficientPermissionException(
                "User does not have {$permission->value} permission for this document"
            );
        }
    }

    /**
     * Get shares for document
     */
    public function getDocumentShares(string $documentId): Collection
    {
        return $this->shareRepository->getByDocument($documentId);
    }

    /**
     * Get active shares for document
     */
    public function getActiveShares(string $documentId): Collection
    {
        return $this->shareRepository->getActiveByDocument($documentId);
    }

    /**
     * Get documents shared with user
     */
    public function getSharedWithUser(string $userId): Collection
    {
        return $this->documentRepository->model
            ->whereHas('shares', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->where('is_latest_version', true)
            ->get();
    }

    /**
     * Update share permission
     */
    public function updatePermission(string $shareId, PermissionType $permissionType): DocumentShare
    {
        return TransactionHelper::execute(function () use ($shareId, $permissionType) {
            $share = $this->shareRepository->findOrFail($shareId);

            $share->update(['permission_type' => $permissionType]);

            return $share->fresh();
        });
    }

    /**
     * Extend share expiration
     */
    public function extendExpiration(string $shareId, \DateTimeInterface $newExpiresAt): DocumentShare
    {
        return TransactionHelper::execute(function () use ($shareId, $newExpiresAt) {
            $share = $this->shareRepository->findOrFail($shareId);

            $share->update(['expires_at' => $newExpiresAt]);

            return $share->fresh();
        });
    }

    /**
     * Clean up expired shares
     */
    public function cleanupExpired(): int
    {
        return $this->shareRepository->deleteExpired();
    }

    /**
     * Get user permissions for document
     */
    public function getUserPermissions(string $documentId, string $userId): array
    {
        $document = $this->documentRepository->findById($documentId);

        // Owner has all permissions
        if ($document->owner_id === $userId) {
            return [
                PermissionType::VIEW,
                PermissionType::DOWNLOAD,
                PermissionType::EDIT,
                PermissionType::DELETE,
                PermissionType::SHARE,
            ];
        }

        // Public documents
        if ($document->isPublic()) {
            return [PermissionType::VIEW, PermissionType::DOWNLOAD];
        }

        // Check share
        $share = $this->shareRepository->getByUserAndDocument($userId, $documentId);

        if (! $share || ! $share->isActive()) {
            return [];
        }

        $permissions = [PermissionType::VIEW];

        if ($share->permission_type->includes(PermissionType::DOWNLOAD)) {
            $permissions[] = PermissionType::DOWNLOAD;
        }

        if ($share->permission_type->includes(PermissionType::EDIT)) {
            $permissions[] = PermissionType::EDIT;
        }

        if ($share->permission_type->includes(PermissionType::DELETE)) {
            $permissions[] = PermissionType::DELETE;
        }

        if ($share->permission_type === PermissionType::SHARE) {
            $permissions[] = PermissionType::SHARE;
        }

        return $permissions;
    }
}
