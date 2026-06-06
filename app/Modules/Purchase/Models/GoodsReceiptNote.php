<?php

declare(strict_types=1);

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;

final class GoodsReceiptNote extends CoreModel
{
    use SoftDeletes;

    protected $table = 'goods_receipt_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'purchase_order_id' => 'integer',
            'supplier_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'received_date' => 'date',
            'status' => GoodsReceiptNoteStatus::class,
            'subtotal' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'tax_total' => 'decimal:6',
            'charge_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ]);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GoodsReceiptNoteLine::class, 'goods_receipt_note_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PurchaseHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'goods_receipt_note');
    }
}
