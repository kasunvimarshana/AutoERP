<?php

declare(strict_types=1);

namespace Modules\Selling\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Invoice\Models\Invoice;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\User;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class Sale extends TenantOwnedModel
{
    protected $table = 'sales';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'customer_id' => 'integer',
            'warehouse_id' => 'integer',
            'warehouse_location_id' => 'integer',
            'invoice_id' => 'integer',
            'sale_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'created_by' => 'integer',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class, 'warehouse_location_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class, 'sale_id')->orderBy('line_number');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
