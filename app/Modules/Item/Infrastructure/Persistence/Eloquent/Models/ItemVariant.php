<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\Item;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemIdentifier;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeValue;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemVariant extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_variants';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'cost_price' => 'decimal:4',
            'sales_price' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeValue::class, 'variant_id');
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(ItemIdentifier::class, 'variant_id');
    }
}
