<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\Concerns\HasPurchaseRowVersion;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\UserModel;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class GoodsReceiptNote extends TenantOwnedModel
{
    use HasPurchaseRowVersion;
    use SoftDeletes;

    protected $table = 'goods_receipt_notes';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (GoodsReceiptNote $grn): void {
            $originalStatus = $grn->getOriginal('status');
            $originalStatusEnum = $originalStatus instanceof GoodsReceiptNoteStatus
                ? $originalStatus
                : ($originalStatus !== null ? GoodsReceiptNoteStatus::tryFrom((string) $originalStatus) : null);

            if ($originalStatusEnum !== null && $originalStatusEnum !== GoodsReceiptNoteStatus::Draft) {
                $immutableAttributes = [
                    'grn_number',
                    'received_date',
                    'supplier_id',
                    'warehouse_id',
                    'warehouse_location_id',
                    'purchase_order_id',
                    'subtotal',
                    'discount_total',
                    'tax_total',
                    'charge_total',
                    'grand_total',
                    'tenant_id',
                    'organization_unit_id',
                ];
                if ($grn->isDirty($immutableAttributes)) {
                    throw new LogicException('Posted goods receipt note attributes are immutable in place. Use governed reversal instead.');
                }
            }
        });

        self::deleting(static function (GoodsReceiptNote $grn): void {
            $status = $grn->status instanceof GoodsReceiptNoteStatus
                ? $grn->status
                : GoodsReceiptNoteStatus::from((string) $grn->status);

            if ($status !== GoodsReceiptNoteStatus::Draft) {
                throw new LogicException('Only draft goods receipt notes can be deleted. Use governed reversal to reverse posted receipts.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'posted_by');
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
