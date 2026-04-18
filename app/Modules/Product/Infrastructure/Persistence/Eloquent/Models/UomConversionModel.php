<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class UomConversionModel extends Model
{
    use HasAudit;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'factor',
    ];

    protected $casts = [
        'factor' => 'decimal:10',
    ];
}
