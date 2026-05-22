<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class SupplierItem extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'supplier_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'lead_time_days' => 'integer',
            'min_order_qty' => 'decimal:4',
            'is_preferred' => 'boolean',
            'last_purchase_price' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function preferred(Builder $query): void
    {
        $query->where('is_preferred', true);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo('Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item', 'item_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant',
            'variant_id'
        );
    }
}
