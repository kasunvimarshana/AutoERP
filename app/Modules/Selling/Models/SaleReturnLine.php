<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Selling\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Inventory\Models\InventoryMovement;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class SaleReturnLine extends TenantOwnedModel
{
    protected $table = 'sale_return_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::updating(static function (SaleReturnLine $line): void {
            if ($line->saleReturn && $line->saleReturn->posted_at !== null) {
                $immutable = ['sale_return_id', 'sale_line_id', 'quantity', 'credit_amount', 'tenant_id', 'organization_unit_id'];
                if ($line->isDirty($immutable)) {
                    throw new LogicException('Lines of posted sale returns are immutable in place.');
                }
            }
        });

        self::deleting(static function (SaleReturnLine $line): void {
            if ($line->saleReturn && $line->saleReturn->posted_at !== null) {
                throw new LogicException('Lines of posted sale returns cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'sale_return_id' => 'integer',
            'sale_line_id' => 'integer',
            'quantity' => 'decimal:6',
            'credit_amount' => 'decimal:6',
            'inventory_movement_id' => 'integer',
        ]);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class, 'sale_line_id');
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }
}
