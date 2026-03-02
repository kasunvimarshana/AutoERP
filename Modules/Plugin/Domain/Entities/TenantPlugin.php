<?php

declare(strict_types=1);

namespace Modules\Plugin\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * TenantPlugin entity.
 *
 * Represents a plugin enabled or disabled for a specific tenant.
 * Uses HasTenant to enforce row-level tenant isolation.
 */
class TenantPlugin extends Model
{
    use HasTenant;

    protected $table = 'tenant_plugins';

    protected $fillable = [
        'tenant_id',
        'plugin_manifest_id',
        'enabled',
        'enabled_at',
        'disabled_at',
        'settings',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        'enabled_at'  => 'datetime',
        'disabled_at' => 'datetime',
        'settings'    => 'array',
    ];

    /**
     * The plugin manifest this tenant installation references.
     */
    public function pluginManifest(): BelongsTo
    {
        return $this->belongsTo(PluginManifest::class, 'plugin_manifest_id');
    }
}
