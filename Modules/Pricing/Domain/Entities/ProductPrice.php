<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * ProductPrice entity.
 *
 * Represents a buying/selling price for a product within a price list.
 * Monetary columns are cast as string to enable BCMath-safe arithmetic — never float.
 */
class ProductPrice extends Model
{
    use HasTenant;

    protected $table = 'product_prices';

    protected $fillable = [
        'tenant_id',
        'price_list_id',
        'product_id',
        'uom_id',
        'buying_price',
        'selling_price',
        'min_quantity',
        'valid_from',
        'valid_to',
    ];

    /**
     * Cast price and quantity columns as string for BCMath precision — never cast to float.
     */
    protected $casts = [
        'buying_price'  => 'string',
        'selling_price' => 'string',
        'min_quantity'  => 'string',
        'valid_from'    => 'date',
        'valid_to'      => 'date',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }
}
