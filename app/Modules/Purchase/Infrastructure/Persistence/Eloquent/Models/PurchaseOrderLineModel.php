<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemVariantModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnLineModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnLineModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class PurchaseOrderLineModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, SoftDeletes;

    protected $table = 'purchase_order_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:4',
            'discount_value' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'line_total' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'metadata' => 'array',
            'ordered_qty' => 'decimal:4',
            'received_qty' => 'decimal:4',
            'rejected_qty' => 'decimal:4',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'unit_price' => 'decimal:4',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderModel::class, 'purchase_order_id');
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'tax_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasureModel::class, 'uom_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariantModel::class, 'variant_id');
    }

    public function grnLines(): HasMany
    {
        return $this->hasMany(GrnLineModel::class, 'purchase_order_line_id');
    }

    public function purchaseReturnLines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLineModel::class, 'original_purchase_order_line_id');
    }

}
