<?php

declare(strict_types=1);

namespace Modules\Document\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Document\Enums\PermissionType;

/**
 * DocumentShare Model
 *
 * Manages document sharing and permissions
 */
class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'user_id',
        'permission_type',
        'expires_at',
    ];

    protected $casts = [
        'permission_type' => PermissionType::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Get the shared document
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user this document is shared with
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if share has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if share is active
     */
    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if share grants specific permission
     */
    public function hasPermission(PermissionType $permission): bool
    {
        return $this->isActive() && $this->permission_type->includes($permission);
    }
}
