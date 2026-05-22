<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemVariantAttributeValue extends Model
{
    use HasTenantAndOrganization;

    public $timestamps = false;

    protected $table = 'item_variant_attribute_values';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(ItemAttributeValue::class, 'attribute_value_id');
    }
}
