<?php

declare(strict_types=1);

namespace Modules\Document\Policies;

use Modules\Auth\Models\User;
use Modules\Document\Enums\PermissionType;
use Modules\Document\Models\Document;
use Modules\Document\Services\DocumentShareService;

class DocumentPolicy
{
    public function __construct(
        private ?DocumentShareService $shareService = null
    ) {
        if (! $this->shareService) {
            $this->shareService = app(DocumentShareService::class);
        }
    }

    /**
     * Determine if user can view any documents
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can view the document
     */
    public function view(User $user, Document $document): bool
    {
        // Owner can always view
        if ($document->owner_id === $user->id) {
            return true;
        }

        // Check if document is public
        if ($document->isPublic()) {
            return true;
        }

        // Check share permissions
        return $this->shareService->checkPermission($document->id, $user->id, PermissionType::VIEW);
    }

    /**
     * Determine if user can create documents
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can update the document
     */
    public function update(User $user, Document $document): bool
    {
        // Owner can always update
        if ($document->owner_id === $user->id) {
            return true;
        }

        // Check edit permission
        return $this->shareService->checkPermission($document->id, $user->id, PermissionType::EDIT);
    }

    /**
     * Determine if user can delete the document
     */
    public function delete(User $user, Document $document): bool
    {
        // Owner can always delete
        if ($document->owner_id === $user->id) {
            return true;
        }

        // Check delete permission
        return $this->shareService->checkPermission($document->id, $user->id, PermissionType::DELETE);
    }

    /**
     * Determine if user can download the document
     */
    public function download(User $user, Document $document): bool
    {
        // Owner can always download
        if ($document->owner_id === $user->id) {
            return true;
        }

        // Public documents can be downloaded
        if ($document->isPublic()) {
            return true;
        }

        // Check download permission
        return $this->shareService->checkPermission($document->id, $user->id, PermissionType::DOWNLOAD);
    }

    /**
     * Determine if user can share the document
     */
    public function share(User $user, Document $document): bool
    {
        // Owner can always share
        if ($document->owner_id === $user->id) {
            return true;
        }

        // Check share permission
        return $this->shareService->checkPermission($document->id, $user->id, PermissionType::SHARE);
    }

    /**
     * Determine if user can restore the document
     */
    public function restore(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    /**
     * Determine if user can permanently delete the document
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }
}
