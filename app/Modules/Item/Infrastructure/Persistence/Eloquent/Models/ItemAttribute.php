<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Item\Domain\Enums\ItemAttributeType;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeGroup;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemAttributeValue;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantAttribute;
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemAttribute extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_attributes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => ItemAttributeType::class,
            'is_required' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ItemAttributeGroup::class, 'group_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ItemAttributeValue::class, 'attribute_id');
    }

    public function variantAssignments(): HasMany
    {
        return $this->hasMany(ItemVariantAttribute::class, 'attribute_id');
    }
}
