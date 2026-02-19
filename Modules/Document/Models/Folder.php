<?php

declare(strict_types=1);

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Folder Model
 *
 * Hierarchical folder structure for organizing documents
 */
class Folder extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'parent_folder_id',
        'name',
        'description',
        'path',
        'is_system',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id');
    }

    /**
     * Get child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_folder_id');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get documents in this folder
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    /**
     * Get the user who created this folder
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if folder is root
     */
    public function isRoot(): bool
    {
        return $this->parent_folder_id === null;
    }

    /**
     * Get full path with names
     */
    public function getFullPath(): string
    {
        $path = $this->name;
        $parent = $this->parent;

        while ($parent !== null) {
            $path = $parent->name.'/'.$path;
            $parent = $parent->parent;
        }

        return '/'.$path;
    }

    /**
     * Update path for this folder and all descendants
     */
    public function updatePaths(): void
    {
        $this->path = $this->getFullPath();
        $this->saveQuietly();

        foreach ($this->children as $child) {
            $child->updatePaths();
        }
    }
}
