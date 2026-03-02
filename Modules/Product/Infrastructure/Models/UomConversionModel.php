<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class UomConversionModel extends Model
{
    use BelongsToTenant;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'from_uom',
        'to_uom',
        'factor',
    ];

    protected $casts = [
        'factor' => 'string',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
