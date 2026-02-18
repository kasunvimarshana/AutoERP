<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

/**
 * Pricing Rule Model
 *
 * Represents flexible pricing rules for products.
 */
class PricingRule extends BaseModel
{
    use HasFactory;

    protected $table = 'pricing_rules';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'rule_type',
        'conditions',
        'price',
        'discount_percentage',
        'valid_from',
        'valid_to',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'price' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
