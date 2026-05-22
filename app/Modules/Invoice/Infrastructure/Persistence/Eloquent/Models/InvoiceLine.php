<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Invoice\Domain\Enums\InvoiceDiscountType;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class InvoiceLine extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'invoice_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_type' => InvoiceDiscountType::class,
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Invoice\\Infrastructure\\Persistence\\Eloquent\\Models\\Invoice',
            'invoice_id'
        );
    }

    public function invoiceReference(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Invoice\\Infrastructure\\Persistence\\Eloquent\\Models\\InvoiceReference',
            'invoice_reference_id'
        );
    }

    public function uom(): BelongsTo
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
