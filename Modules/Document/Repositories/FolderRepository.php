<?php

declare(strict_types=1);

namespace Modules\Document\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Document\Exceptions\FolderNotFoundException;
use Modules\Document\Models\Folder;

/**
 * Folder Repository
 *
 * Handles data access for folders
 */
class FolderRepository extends BaseRepository
{
    public function __construct(Folder $model)
    {
        parent::__construct($model);
    }

    /**
     * Find folder by ID
     *
     * @throws FolderNotFoundException
     */
    public function findById(string $id): Folder
    {
        $folder = $this->model->with(['parent', 'children'])->find($id);

        if (! $folder) {
            throw new FolderNotFoundException("Folder with ID {$id} not found");
        }

        return $folder;
    }

    /**
     * Get root folders
     */
    public function getRootFolders(): Collection
    {
        return $this->model->whereNull('parent_folder_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get folder tree
     */
    public function getTree(): Collection
    {
        return $this->model->whereNull('parent_folder_id')
            ->with('descendants')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get children of folder
     */
    public function getChildren(string $folderId): Collection
    {
        return $this->model->where('parent_folder_id', $folderId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get folder by path
     */
    public function findByPath(string $path): ?Folder
    {
        return $this->model->where('path', $path)->first();
    }

    /**
     * Check if folder has children
     */
    public function hasChildren(string $folderId): bool
    {
        return $this->model->where('parent_folder_id', $folderId)->exists();
    }

    /**
     * Check if folder has documents
     */
    public function hasDocuments(string $folderId): bool
    {
        return $this->model->find($folderId)?->documents()->exists() ?? false;
    }
}
