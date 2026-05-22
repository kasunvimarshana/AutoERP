<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class SupplierPriceList extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'supplier_price_lists';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'priority' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Models\\Supplier',
            'supplier_id'
        );
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\PriceList',
            'price_list_id'
        );
    }
}
