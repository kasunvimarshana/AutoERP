<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class UomConversionModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'uom_conversions';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'from_uom',
        'to_uom',
        'factor',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'factor'     => 'decimal:10',
        'is_active'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
