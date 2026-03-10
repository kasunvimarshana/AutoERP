<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Product Eloquent Model
 *
 * @property string              $id
 * @property string              $tenant_id
 * @property string              $category_id
 * @property string              $name
 * @property string              $code
 * @property string|null         $description
 * @property float               $price
 * @property string              $currency
 * @property string              $status
 * @property array<string,mixed> $attributes
 * @property array<string,mixed> $metadata
 */
class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'code',
        'sku',
        'barcode',
        'description',
        'price',
        'cost_price',
        'currency',
        'unit',
        'weight',
        'dimensions',
        'status',
        'attributes',
        'metadata',
        'image_url',
        'is_trackable',
    ];

    protected $casts = [
        'price'        => 'float',
        'cost_price'   => 'float',
        'weight'       => 'float',
        'attributes'   => 'array',
        'dimensions'   => 'array',
        'metadata'     => 'array',
        'is_trackable' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }
}
