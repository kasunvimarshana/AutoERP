<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class GrnHeader extends CoreModel
{
    use SoftDeletes;

    protected $table = 'grn_headers';

    protected $guarded = ['id'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GrnLine::class, 'grn_header_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'metadata' => 'array',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'purchase_order_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:4',
            'received_date' => 'date',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_group_id' => 'integer',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'charge_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
        ]);
    }
}
