<?php

declare(strict_types=1);

namespace Modules\Document\Policies;

use Modules\Auth\Models\User;
use Modules\Document\Models\Folder;

class FolderPolicy
{
    /**
     * Determine if user can view any folders
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can view the folder
     */
    public function view(User $user, Folder $folder): bool
    {
        // Users in same organization can view folders
        return $user->organization_id === $folder->organization_id;
    }

    /**
     * Determine if user can create folders
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if user can update the folder
     */
    public function update(User $user, Folder $folder): bool
    {
        // Cannot update system folders
        if ($folder->is_system) {
            return false;
        }

        // Users in same organization can update
        return $user->organization_id === $folder->organization_id;
    }

    /**
     * Determine if user can delete the folder
     */
    public function delete(User $user, Folder $folder): bool
    {
        // Cannot delete system folders
        if ($folder->is_system) {
            return false;
        }

        // Users in same organization can delete
        return $user->organization_id === $folder->organization_id;
    }

    /**
     * Determine if user can restore the folder
     */
    public function restore(User $user, Folder $folder): bool
    {
        return $user->organization_id === $folder->organization_id;
    }

    /**
     * Determine if user can permanently delete the folder
     */
    public function forceDelete(User $user, Folder $folder): bool
    {
        if ($folder->is_system) {
            return false;
        }

        return $user->organization_id === $folder->organization_id;
    }
}
