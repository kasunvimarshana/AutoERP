<?php

declare(strict_types=1);

namespace Modules\Metadata\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * FeatureFlag entity.
 *
 * Stores tenant-level feature toggles.
 * All feature gating must be driven by this table — no hardcoded flags.
 */
class FeatureFlag extends Model
{
    use HasTenant;

    protected $table = 'feature_flags';

    protected $fillable = [
        'tenant_id',
        'flag_key',
        'flag_value',
        'description',
    ];

    protected $casts = [
        'flag_value' => 'boolean',
    ];
}
