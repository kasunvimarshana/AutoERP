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
use Modules\Item\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class ItemBrand extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'item_brands';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'depth' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ItemBrand::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ItemBrand::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'brand_id');
    }
}
