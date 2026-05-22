<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pricing\Domain\Enums\DiscountType;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class VehicleServiceLaborItem extends Model
{
    use HasTenantAndOrganization;

    protected $table = 'vehicle_service_labor_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'gross_amount' => 'decimal:4',
            'line_total' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'line_total_with_tax' => 'decimal:4',
            'incentive_value' => 'decimal:4',
            'incentive_amount' => 'decimal:4',
        ];
    }

    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceJobCard',
            'job_card_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\Item',
            'item_id'
        );
    }

    public function comboItem(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Item\\Infrastructure\\Persistence\\Eloquent\\Models\\ComboItem',
            'combo_item_id'
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

    public function assignments(): HasMany
    {
        return $this->hasMany(
            'Modules\\VehicleService\\Infrastructure\\Persistence\\Eloquent\\Models\\VehicleServiceLaborAssignment',
            'labor_item_id'
        );
    }
}
