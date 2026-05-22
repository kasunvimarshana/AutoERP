<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pricing\Domain\Enums\PriceListType;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class PriceList extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'price_lists';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'type' => PriceListType::class,
            'is_default' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\PriceListItem',
            'price_list_id'
        );
    }

    public function supplierAssignments(): HasMany
    {
        return $this->hasMany(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\SupplierPriceList',
            'price_list_id'
        );
    }

    public function customerAssignments(): HasMany
    {
        return $this->hasMany(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\CustomerPriceList',
            'price_list_id'
        );
    }
}
