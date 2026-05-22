<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Purchase\Domain\Enums\PurchaseReturnStatus;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeader;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLine;

class PurchaseReturn extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'purchase_returns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => PurchaseReturnStatus::class,
            'exchange_rate' => 'decimal:4',
            'return_date' => 'date',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'line_restocking_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Supplier\\Infrastructure\\Persistence\\Eloquent\\Models\\Supplier',
            'supplier_id'
        );
    }

    public function originalPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'original_purchase_order_id');
    }

    public function originalGrn(): BelongsTo
    {
        return $this->belongsTo(GrnHeader::class, 'original_grn_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'header_tax_group_id'
        );
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class, 'purchase_return_id');
    }
}
