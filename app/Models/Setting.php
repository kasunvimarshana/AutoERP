<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setting Model
 *
 * Stores system and tenant-specific configuration settings
 * Supports both global (tenant_id = null) and tenant-specific settings
 */
class Setting extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
        'is_public',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the tenant that owns the setting (nullable for global settings)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    /**
     * Check if this is a global setting
     */
    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Check if this setting is public
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }
}
