<?php

declare(strict_types=1);

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Traits\Auditable;
use Modules\Auth\Models\User;
use Modules\Document\Enums\AccessLevel;
use Modules\Document\Enums\DocumentStatus;
use Modules\Document\Enums\DocumentType;
use Modules\Tenant\Traits\TenantScoped;

/**
 * Document Model
 *
 * Main document entity with versioning and access control
 */
class Document extends Model
{
    use Auditable, HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'folder_id',
        'owner_id',
        'name',
        'description',
        'type',
        'mime_type',
        'size',
        'path',
        'original_name',
        'extension',
        'version',
        'is_latest_version',
        'parent_document_id',
        'access_level',
        'status',
        'metadata',
        'download_count',
        'view_count',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'access_level' => AccessLevel::class,
        'metadata' => 'array',
        'size' => 'integer',
        'version' => 'integer',
        'is_latest_version' => 'boolean',
        'download_count' => 'integer',
        'view_count' => 'integer',
    ];

    /**
     * Get the folder containing this document
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get the document owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the parent document (for versions)
     */
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    /**
     * Get document versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /**
     * Get all version documents
     */
    public function versionDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    /**
     * Get document tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class, 'document_tag_relations');
    }

    /**
     * Get document shares
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * Get document activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(DocumentActivity::class);
    }

    /**
     * Check if document is published
     */
    public function isPublished(): bool
    {
        return $this->status === DocumentStatus::PUBLISHED;
    }

    /**
     * Check if document is draft
     */
    public function isDraft(): bool
    {
        return $this->status === DocumentStatus::DRAFT;
    }

    /**
     * Check if document is archived
     */
    public function isArchived(): bool
    {
        return $this->status === DocumentStatus::ARCHIVED;
    }

    /**
     * Check if document is public
     */
    public function isPublic(): bool
    {
        return $this->access_level === AccessLevel::PUBLIC;
    }

    /**
     * Check if document is private
     */
    public function isPrivate(): bool
    {
        return $this->access_level === AccessLevel::PRIVATE;
    }

    /**
     * Check if document is shared
     */
    public function isShared(): bool
    {
        return $this->access_level === AccessLevel::SHARED;
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get human-readable file size
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Publish the document
     */
    public function publish(): void
    {
        $this->update(['status' => DocumentStatus::PUBLISHED]);
    }

    /**
     * Archive the document
     */
    public function archive(): void
    {
        $this->update(['status' => DocumentStatus::ARCHIVED]);
    }

    /**
     * Make document public
     */
    public function makePublic(): void
    {
        $this->update(['access_level' => AccessLevel::PUBLIC]);
    }

    /**
     * Make document private
     */
    public function makePrivate(): void
    {
        $this->update(['access_level' => AccessLevel::PRIVATE]);
    }
}
