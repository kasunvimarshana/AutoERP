<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;

abstract class Agreement extends TenantOwnedModel
{
    protected function casts(): array
    {
        return array_merge(parent::casts(), ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'row_version' => 'integer', 'terms' => 'array', 'status' => AgreementStatus::class, 'basis' => RentalBasis::class, 'driver_mode' => DriverMode::class, 'executing_on' => 'date:'.AgreementFields::DATE_FORMAT, 'agreed_on' => 'date:'.AgreementFields::DATE_FORMAT, 'starts_on' => 'date:'.AgreementFields::DATE_FORMAT, 'ends_on' => 'date:'.AgreementFields::DATE_FORMAT, 'activated_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime']);
    }

    public function scopeForContext(Builder $query, int $tenantId, int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)->where('organization_unit_id', $organizationUnitId);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    protected static function booted(): void
    {
        static::deleting(static fn () => throw new LogicException('Agreement history must be preserved.'));
        static::updating(static function (self $agreement): void {
            if ($agreement->getRawOriginal('status') !== AgreementStatus::Draft->value
                && array_diff(array_keys($agreement->getDirty()), ['status', 'closed_at', 'row_version', 'updated_at']) !== []) {
                throw new LogicException('Activated agreement terms are immutable.');
            }
        });
    }
}
