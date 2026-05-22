<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\VehicleService\Domain\Enums\JobCardPriority;
use Modules\VehicleService\Domain\Enums\JobCardStatus;
use Modules\VehicleService\Domain\Enums\VehicleServiceInvoiceStatus;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceJobCard extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'vehicle_service_job_cards';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'priority' => JobCardPriority::class,
            'status' => JobCardStatus::class,
            'invoice_status' => VehicleServiceInvoiceStatus::class,
            'exchange_rate' => 'decimal:4',
            'start_datetime' => 'datetime',
            'completed_datetime' => 'datetime',
            'estimated_hours' => 'decimal:4',
            'actual_hours' => 'decimal:4',
            'promised_delivery_date_time' => 'datetime',
            'warranty_eligible' => 'boolean',
            'next_service_date' => 'date',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'non_inventory_item_subtotal' => 'decimal:4',
            'non_inventory_item_tax_total' => 'decimal:4',
            'non_inventory_item_discount_total' => 'decimal:4',
            'labour_item_subtotal' => 'decimal:4',
            'labour_item_tax_total' => 'decimal:4',
            'labour_item_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance' => 'decimal:4',
        ];
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceType::class, 'service_type_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Models\\Customer',
            'customer_id'
        );
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Vehicle\\Infrastructure\\Persistence\\Eloquent\\Models\\Vehicle',
            'vehicle_id'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\Warehouse',
            'warehouse_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\PriceList',
            'price_list_id'
        );
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'header_tax_group_id'
        );
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\HR\\Infrastructure\\Persistence\\Eloquent\\Models\\Employee',
            'assigned_to'
        );
    }

    public function itemLines(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceJobCardLine',
            'job_card_id'
        );
    }

    public function laborItems(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceLaborItem',
            'job_card_id'
        );
    }

    public function nonInventoryItems(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceNonInventoryItem',
            'job_card_id'
        );
    }

    public function laborAssignments(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceLaborAssignment',
            'job_card_id'
        );
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceDiagnostic',
            'job_card_id'
        );
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceInspection',
            'job_card_id'
        );
    }
}
