<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Enums\RentalCalculationStatus;

final class RentalCalculation extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_calculations';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'rate_version_id' => 'integer',
            'currency_id' => 'integer',
            'side' => RentalCalculationSide::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'chart_count' => 'integer',
            'operating_days' => 'integer',
            'commercial_km' => 'decimal:6',
            'included_km' => 'decimal:6',
            'excess_km' => 'decimal:6',
            'subtotal_amount' => 'decimal:6',
            'status' => RentalCalculationStatus::class,
            'active_marker' => 'boolean',
            'created_by' => 'integer',
            'cancelled_by' => 'integer',
            'cancelled_at' => 'datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);

        return $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function rateVersion(): BelongsTo
    {
        return $this->belongsTo(RentalRateVersion::class, 'rate_version_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentalCalculationLine::class, 'calculation_id')->orderBy('line_number');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(RentalCalculationSource::class, 'calculation_id');
    }
}
