<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemVariantAttribute extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_variant_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'is_required' => 'boolean',
            'is_variation_axis' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ItemAttribute::class, 'attribute_id');
    }
}
