<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ItemTypeModel extends Model
{
    use SoftDeletes;

    protected $table = 'item_types';

    protected $guarded = [];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_stockable' => 'boolean',
        'is_service' => 'boolean',
        'is_rentable' => 'boolean',
        'is_chargeable' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];
}
