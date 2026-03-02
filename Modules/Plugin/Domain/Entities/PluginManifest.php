<?php

declare(strict_types=1);

namespace Modules\Plugin\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PluginManifest entity.
 *
 * Represents a globally-registered plugin in the marketplace.
 * This is NOT tenant-scoped — manifests are global registry entries
 * shared across all tenants.
 */
class PluginManifest extends Model
{
    use SoftDeletes;

    protected $table = 'plugin_manifests';

    protected $fillable = [
        'name',
        'alias',
        'description',
        'version',
        'keywords',
        'requires',
        'active',
        'manifest_data',
    ];

    protected $casts = [
        'keywords'      => 'array',
        'requires'      => 'array',
        'manifest_data' => 'array',
        'active'        => 'boolean',
    ];

    /**
     * The tenant plugin installations for this manifest.
     */
    public function tenantPlugins(): HasMany
    {
        return $this->hasMany(TenantPlugin::class, 'plugin_manifest_id');
    }
}
