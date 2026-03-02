<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class AisleModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'wms_aisles';

    protected $fillable = [
        'tenant_id',
        'zone_id',
        'name',
        'code',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
