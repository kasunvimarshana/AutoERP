<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\TenantOwnedModel;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;

final class RentalRateVersion extends TenantOwnedModel
{
    protected $table = 'vehicle_rental_rate_versions';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'agreement_id' => 'integer',
            'version_number' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'status' => RentalRateVersionStatus::class,
            'created_by' => 'integer',
            'activated_by' => 'integer',
            'activated_at' => 'datetime',
        ]);
    }

    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->where('status', RentalRateVersionStatus::Active->value)
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $scope) use ($date): void {
                $scope->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'agreement_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentalRateLine::class, 'rate_version_id')->orderBy('line_number');
    }
}
