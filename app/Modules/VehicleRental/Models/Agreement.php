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
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'supersedes_agreement_id' => 'integer',
            'terms' => 'array',
            'status' => AgreementStatus::class,
            'basis' => RentalBasis::class,
            'driver_mode' => DriverMode::class,
            'executing_on' => 'date:'.AgreementFields::DATE_FORMAT,
            'agreed_on' => 'date:'.AgreementFields::DATE_FORMAT,
            'starts_on' => 'date:'.AgreementFields::DATE_FORMAT,
            'ends_on' => 'date:'.AgreementFields::DATE_FORMAT,
            'closed_on' => 'date:'.AgreementFields::DATE_FORMAT,
            'activated_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ]);
    }

    public function scopeForContext(Builder $query, int $tenantId, int $organizationUnitId): Builder
    {
        return $query->forTenant($tenantId)->where('organization_unit_id', $organizationUnitId);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function supersedesAgreement(): BelongsTo
    {
        return $this->belongsTo(static::class, 'supersedes_agreement_id');
    }

    protected static function booted(): void
    {
        static::deleting(static fn () => throw new LogicException('Agreement history must be preserved.'));
        static::updating(static function (self $agreement): void {
            $originalStatus = AgreementStatus::from((string) $agreement->getRawOriginal('status'));
            if ($originalStatus === AgreementStatus::Draft) {
                if ($agreement->isDirty('supersedes_agreement_id')) {
                    throw new LogicException('Agreement predecessor lineage is immutable once the successor draft is created.');
                }

                return;
            }

            $allowed = ['row_version', 'updated_at'];
            if ($originalStatus === AgreementStatus::Active && $agreement->status === AgreementStatus::Closed) {
                if ($agreement->closed_at === null || $agreement->closed_on === null) {
                    throw new LogicException('Closing a rental agreement requires both the closure instant and immutable closure civil date.');
                }
                array_push($allowed, 'status', 'ends_on', 'closed_at', 'closed_on');
            }

            if (array_diff(array_keys($agreement->getDirty()), $allowed) !== []) {
                throw new LogicException('Activated agreement terms and recorded lifecycle boundaries are immutable. Use a successor agreement for future commercial changes.');
            }
        });
    }
}
