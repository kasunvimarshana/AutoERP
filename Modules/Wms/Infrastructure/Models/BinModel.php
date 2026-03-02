<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class BinModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'wms_bins';

    protected $fillable = [
        'tenant_id',
        'aisle_id',
        'code',
        'description',
        'max_capacity',
        'current_capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
        'current_capacity' => 'integer',
    ];
}
