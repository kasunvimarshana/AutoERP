<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class GrnLine extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'grn_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'expected_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
        ];
    }

    public function grnHeader(): BelongsTo
    {
        return $this->belongsTo(GrnHeader::class, 'grn_header_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item',
            'item_id'
        );
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ItemVariant',
            'variant_id'
        );
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\Batch',
            'batch_id'
        );
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Inventory\\Infrastructure\\Persistence\\Eloquent\\Models\\Serial',
            'serial_id'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\Warehouse',
            'warehouse_id'
        );
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\WarehouseLocation',
            'location_id'
        );
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\UOM\\Infrastructure\\Persistence\\Eloquent\\Models\\UnitOfMeasure',
            'uom_id'
        );
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'tax_group_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'account_id'
        );
    }
}
