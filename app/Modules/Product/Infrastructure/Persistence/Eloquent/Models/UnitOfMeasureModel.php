<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class UnitOfMeasureModel extends Model
{
    use HasAudit;

    protected $table = 'units_of_measure';

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'type',
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];
}
