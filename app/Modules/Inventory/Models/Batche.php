<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Batche extends BaseModel
{
    protected $table = 'batches';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'supplier_batch',
        'status',
        'attributes',
        'created_at'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'attributes' => 'array',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }
}
