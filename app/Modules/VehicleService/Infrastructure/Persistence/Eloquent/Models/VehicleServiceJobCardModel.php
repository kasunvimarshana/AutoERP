<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerModel;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceDiagnosticModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceInspectionModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceJobCardLineModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborAssignmentModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceLaborItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceNonInventoryItemModel;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models\VehicleServiceTypeModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class VehicleServiceJobCardModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope, SoftDeletes;

    protected $table = 'vehicle_service_job_cards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'actual_hours' => 'decimal:4',
            'balance' => 'decimal:4',
            'completed_datetime' => 'datetime',
            'credit_note_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'end_odometer' => 'integer',
            'estimated_hours' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'labor_item_discount_total' => 'decimal:4',
            'labor_item_subtotal' => 'decimal:4',
            'labor_item_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'metadata' => 'array',
            'next_service_date' => 'date',
            'next_service_odometer' => 'integer',
            'non_inventory_item_discount_total' => 'decimal:4',
            'non_inventory_item_subtotal' => 'decimal:4',
            'non_inventory_item_tax_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'promised_delivery_date_time' => 'datetime',
            'row_version' => 'integer',
            'start_datetime' => 'datetime',
            'start_odometer' => 'integer',
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'warranty_eligible' => 'boolean',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxGroupModel::class, 'header_tax_group_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceListModel::class, 'price_list_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceTypeModel::class, 'service_type_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class, 'warehouse_id');
    }

    public function vehicleServiceDiagnostics(): HasMany
    {
        return $this->hasMany(VehicleServiceDiagnosticModel::class, 'job_card_id');
    }

    public function vehicleServiceInspections(): HasMany
    {
        return $this->hasMany(VehicleServiceInspectionModel::class, 'job_card_id');
    }

    public function vehicleServiceJobCardLines(): HasMany
    {
        return $this->hasMany(VehicleServiceJobCardLineModel::class, 'job_card_id');
    }

    public function vehicleServiceLaborAssignments(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborAssignmentModel::class, 'job_card_id');
    }

    public function vehicleServiceLaborItems(): HasMany
    {
        return $this->hasMany(VehicleServiceLaborItemModel::class, 'job_card_id');
    }

    public function vehicleServiceNonInventoryItems(): HasMany
    {
        return $this->hasMany(VehicleServiceNonInventoryItemModel::class, 'job_card_id');
    }

}
