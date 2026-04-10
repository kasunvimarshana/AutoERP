<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UomConversion extends BaseModel
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'factor',
        'product_id'
    ];

    protected $casts = [
        'factor' => 'decimal:4'
    ];

    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'from_uom_id');
    }

    public function toUom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'to_uom_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }
}
