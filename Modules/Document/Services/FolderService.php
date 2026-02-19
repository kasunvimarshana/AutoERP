<?php

declare(strict_types=1);

namespace Modules\Document\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Document\Models\Folder;
use Modules\Document\Repositories\FolderRepository;

/**
 * FolderService
 *
 * Manages folder operations
 */
class FolderService
{
    public function __construct(
        private FolderRepository $folderRepository,
    ) {}

    /**
     * Create a new folder
     */
    public function createFolder(
        string $name,
        ?string $parentId = null,
        ?string $description = null
    ): Folder {
        return TransactionHelper::execute(function () use ($name, $parentId, $description) {
            $user = auth()->user();

            // Validate parent exists if provided
            if ($parentId) {
                $parent = $this->folderRepository->findById($parentId);
            }

            $folder = $this->folderRepository->create([
                'tenant_id' => $user->tenant_id,
                'organization_id' => $user->organization_id,
                'parent_folder_id' => $parentId,
                'name' => $name,
                'description' => $description,
                'is_system' => false,
                'created_by' => $user->id,
                'metadata' => [],
            ]);

            // Update path
            $folder->path = $folder->getFullPath();
            $folder->saveQuietly();

            return $folder->fresh();
        });
    }

    /**
     * Update folder
     */
    public function updateFolder(string $folderId, array $data): Folder
    {
        return TransactionHelper::execute(function () use ($folderId, $data) {
            $folder = $this->folderRepository->findById($folderId);

            $folder->update($data);

            // Update path if name changed
            if (isset($data['name'])) {
                $folder->updatePaths();
            }

            return $folder->fresh();
        });
    }

    /**
     * Move folder to new parent
     */
    public function move(string $folderId, ?string $newParentId): Folder
    {
        return TransactionHelper::execute(function () use ($folderId, $newParentId) {
            $folder = $this->folderRepository->findById($folderId);

            // Validate new parent exists if provided
            if ($newParentId) {
                $newParent = $this->folderRepository->findById($newParentId);

                // Prevent moving folder into itself or its descendants
                if ($this->isDescendant($newParentId, $folderId)) {
                    throw new \InvalidArgumentException('Cannot move folder into itself or its descendants');
                }
            }

            $folder->update(['parent_folder_id' => $newParentId]);
            $folder->updatePaths();

            return $folder->fresh();
        });
    }

    /**
     * Delete folder
     */
    public function delete(string $folderId, bool $permanent = false): bool
    {
        return TransactionHelper::execute(function () use ($folderId, $permanent) {
            $folder = $this->folderRepository->findById($folderId);

            if ($folder->is_system) {
                throw new \InvalidArgumentException('Cannot delete system folder');
            }

            if ($permanent) {
                // Delete all documents in folder and subfolders
                $this->deleteDocumentsRecursively($folder);

                // Delete all subfolders
                $this->deleteSubfoldersRecursively($folder);

                // Permanently delete folder
                $folder->forceDelete();
            } else {
                // Soft delete
                $folder->delete();
            }

            return true;
        });
    }

    /**
     * Get folder tree
     */
    public function getTree(?string $rootId = null): Collection
    {
        if ($rootId) {
            $folder = $this->folderRepository->findById($rootId);

            return collect([$folder->load('descendants')]);
        }

        return $this->folderRepository->getTree();
    }

    /**
     * Get folder path breadcrumbs
     */
    public function getBreadcrumbs(string $folderId): array
    {
        $folder = $this->folderRepository->findById($folderId);
        $breadcrumbs = [];

        $current = $folder;
        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'path' => $current->path,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Search folders
     */
    public function search(string $query): Collection
    {
        return $this->folderRepository->model
            ->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->get();
    }

    /**
     * Check if folder is descendant of another
     */
    private function isDescendant(string $folderId, string $ancestorId): bool
    {
        $folder = $this->folderRepository->findById($folderId);

        $current = $folder->parent;
        while ($current) {
            if ($current->id === $ancestorId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Delete documents recursively
     */
    private function deleteDocumentsRecursively(Folder $folder): void
    {
        foreach ($folder->documents as $document) {
            $document->forceDelete();
        }

        foreach ($folder->children as $child) {
            $this->deleteDocumentsRecursively($child);
        }
    }

    /**
     * Delete subfolders recursively
     */
    private function deleteSubfoldersRecursively(Folder $folder): void
    {
        foreach ($folder->children as $child) {
            $this->deleteSubfoldersRecursively($child);
            $child->forceDelete();
        }
    }
}
