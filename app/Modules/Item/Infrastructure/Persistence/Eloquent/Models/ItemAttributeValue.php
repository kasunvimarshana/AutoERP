<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttribute;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttributeValue;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemAttributeValue extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_attribute_values';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ItemAttribute::class, 'attribute_id');
    }

    public function variantValues(): HasMany
    {
        return $this->hasMany(ItemVariantAttributeValue::class, 'attribute_value_id');
    }
}
