<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetadataFeatureFlag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'label',
        'description',
        'is_enabled',
        'module',
        'config',
        'enabled_at',
        'disabled_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns the feature flag.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Enable the feature flag.
     */
    public function enable(): void
    {
        $this->update([
            'is_enabled' => true,
            'enabled_at' => now(),
            'disabled_at' => null,
        ]);
    }

    /**
     * Disable the feature flag.
     */
    public function disable(): void
    {
        $this->update([
            'is_enabled' => false,
            'disabled_at' => now(),
        ]);
    }

    /**
     * Scope to enabled flags.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
