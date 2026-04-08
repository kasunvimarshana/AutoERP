<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ProductComboItemModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'product_combo_items';

    protected $fillable = [
        'tenant_id',
        'combo_product_id',
        'component_product_id',
        'quantity',
        'unit_of_measure',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comboProduct(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'combo_product_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'component_product_id');
    }
}
