<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product model – represents a catalogue entry for a tenant.
 *
 * @property string      $id
 * @property string      $tenant_id
 * @property string      $name
 * @property string|null $description
 * @property string      $sku
 * @property float       $price
 * @property string      $currency
 * @property string      $status
 * @property array|null  $metadata
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'price',
        'currency',
        'status',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price'    => 'float',
        'metadata' => 'array',
    ];

    /**
     * The inventory item tracking stock for this product.
     *
     * @return HasOne<InventoryItem>
     */
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class);
    }
}
