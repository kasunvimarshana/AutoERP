<?php

declare(strict_types=1);

namespace Modules\Document\Policies;

use Modules\Auth\Models\User;
use Modules\Document\Models\DocumentTag;

class DocumentTagPolicy
{
    /**
     * Determine if user can view any tags
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.tags.view') || $user->hasRole('admin');
    }

    /**
     * Determine if user can view the tag
     */
    public function view(User $user, DocumentTag $tag): bool
    {
        return $user->tenant_id === $tag->tenant_id &&
               ($user->hasPermission('documents.tags.view') || $user->hasRole('admin'));
    }

    /**
     * Determine if user can create tags
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('documents.tags.create') || $user->hasRole('admin');
    }

    /**
     * Determine if user can update the tag
     */
    public function update(User $user, DocumentTag $tag): bool
    {
        return $user->tenant_id === $tag->tenant_id &&
               ($user->hasPermission('documents.tags.update') || $user->hasRole('admin'));
    }

    /**
     * Determine if user can delete the tag
     */
    public function delete(User $user, DocumentTag $tag): bool
    {
        return $user->tenant_id === $tag->tenant_id &&
               ($user->hasPermission('documents.tags.delete') || $user->hasRole('admin'));
    }
}
