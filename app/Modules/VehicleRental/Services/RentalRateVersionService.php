<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementRateVersion;
use Modules\VehicleRental\Models\RentalUsageContext;

final class RentalRateVersionService
{
    private const OPEN_ENDED_EFFECTIVE_AT = '9999-12-31 23:59:59';

    public function __construct(private readonly RentalReferenceValidator $references) {}

    public function createDraft(RentalAgreement $agreement, array $data, ?int $userId = null): RentalAgreementRateVersion
    {
        return DB::transaction(function () use ($agreement, $data, $userId): RentalAgreementRateVersion {
            $agreement = RentalAgreement::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            if (array_key_exists('expected_agreement_version', $data)) {
                $this->assertAgreementExpectedVersion($agreement, (int) $data['expected_agreement_version']);
            }
            $this->assertAgreementAllowsRateChanges($agreement);

            $versionNumber = ((int) $agreement->rateVersions()->max('version_number')) + 1;
            $effectiveFrom = CarbonImmutable::parse((string) ($data['effective_from'] ?? $agreement->starts_at));
            $effectiveTo = isset($data['effective_to'])
                ? CarbonImmutable::parse((string) $data['effective_to'])
                : null;
            if ($effectiveTo !== null && ! $effectiveTo->greaterThan($effectiveFrom)) {
                throw new InvalidArgumentException('Rate version end must be after its start.');
            }
            if ($effectiveFrom->lessThan($agreement->starts_at)
                || $effectiveFrom->greaterThanOrEqualTo($agreement->ends_at)
                || ($effectiveTo !== null && $effectiveTo->greaterThan($agreement->ends_at))) {
                throw new InvalidArgumentException('Rate version period must stay inside the agreement period.');
            }

            $this->references->currency((int) ($data['currency_id'] ?? $agreement->currency_id));
            $this->references->taxGroup(
                isset($data['tax_group_id']) ? (int) $data['tax_group_id'] : null,
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
            );
            $this->references->taxGroup(
                isset($data['withholding_tax_group_id']) ? (int) $data['withholding_tax_group_id'] : null,
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
            );
            foreach ($data['components'] ?? [] as $component) {
                $this->assertSupportedComponentUnit(
                    RentalRateComponentCode::from((string) $component['component_code']),
                    RentalRateUnit::from((string) $component['unit']),
                );
                $this->references->vehicleCategory(
                    isset($component['vehicle_category_id']) ? (int) $component['vehicle_category_id'] : null,
                    (int) $agreement->tenant_id,
                    $agreement->organization_unit_id,
                );
                $this->references->taxGroup(
                    isset($component['tax_group_override_id']) ? (int) $component['tax_group_override_id'] : null,
                    (int) $agreement->tenant_id,
                    $agreement->organization_unit_id,
                );
            }

            $fingerprint = hash('sha256', implode('|', [
                $agreement->tenant_id,
                $agreement->getKey(),
                $versionNumber,
                $effectiveFrom->toIso8601String(),
                $effectiveTo?->toIso8601String() ?? '',
            ]));

            $version = RentalAgreementRateVersion::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'version_number' => $versionNumber,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'driver_mode' => $data['driver_mode'] ?? $agreement->rental_mode->value,
                'billing_cycle' => $data['billing_cycle'] ?? $agreement->billing_cycle->value,
                'billing_basis' => $data['billing_basis'] ?? $agreement->billing_basis->value,
                'proration_rule' => $data['proration_rule'] ?? $agreement->proration_rule->value,
                'excess_km_method' => $data['excess_km_method'] ?? 'period',
                'included_km' => $data['included_km'] ?? '0.000000',
                'included_hours' => $data['included_hours'] ?? '0.000000',
                'weekday_included_minutes' => $data['weekday_included_minutes'] ?? 0,
                'saturday_included_minutes' => $data['saturday_included_minutes'] ?? 0,
                'holiday_included_minutes' => $data['holiday_included_minutes'] ?? 0,
                'currency_id' => $data['currency_id'] ?? $agreement->currency_id,
                'tax_group_id' => $data['tax_group_id'] ?? null,
                'withholding_tax_group_id' => $data['withholding_tax_group_id'] ?? null,
                'status' => RentalRateVersionStatus::Draft->value,
                'fingerprint' => $fingerprint,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach (array_values($data['components'] ?? []) as $index => $component) {
                $version->components()->create([
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'vehicle_category_id' => $component['vehicle_category_id'] ?? null,
                    'component_code' => $component['component_code'],
                    'unit' => $component['unit'],
                    'included_quantity' => $component['included_quantity'] ?? '0.000000',
                    'rate' => $component['rate'] ?? '0.000000',
                    'multiplier' => $component['multiplier'] ?? '1.000000',
                    'minimum_amount' => $component['minimum_amount'] ?? null,
                    'maximum_amount' => $component['maximum_amount'] ?? null,
                    'tax_group_override_id' => $component['tax_group_override_id'] ?? null,
                    'is_taxable' => $component['is_taxable'] ?? true,
                    'calculation_order' => $component['calculation_order'] ?? ($index + 1),
                    'status' => 'active',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            return $version->refresh()->load('components');
        }, 3);
    }

    public function activate(
        RentalAgreementRateVersion $version,
        int $expectedVersion,
        ?int $userId = null,
    ): RentalAgreementRateVersion {
        return DB::transaction(function () use ($version, $expectedVersion, $userId): RentalAgreementRateVersion {
            $version = RentalAgreementRateVersion::query()
                ->where('tenant_id', $version->tenant_id)
                ->lockForUpdate()
                ->findOrFail($version->getKey());
            $this->assertExpectedVersion($version, $expectedVersion);

            $agreement = RentalAgreement::query()
                ->where('tenant_id', $version->tenant_id)
                ->lockForUpdate()
                ->findOrFail($version->agreement_id);
            $this->assertAgreementAllowsRateChanges($agreement);

            if ($version->status !== RentalRateVersionStatus::Draft) {
                throw new InvalidArgumentException('Only a draft rate version can be activated.');
            }
            if (! $version->components()->exists()) {
                throw new InvalidArgumentException('At least one rate component is required.');
            }

            $overlaps = RentalAgreementRateVersion::query()
                ->where('tenant_id', $version->tenant_id)
                ->where('agreement_id', $version->agreement_id)
                ->whereKeyNot($version->getKey())
                ->where('status', RentalRateVersionStatus::Active->value)
                ->where('effective_from', '<', $version->effective_to ?? self::OPEN_ENDED_EFFECTIVE_AT)
                ->where(function ($query) use ($version): void {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>', $version->effective_from);
                })
                ->lockForUpdate()
                ->get(['id']);
            if ($overlaps->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'effective_from' => ['The rate period overlaps an active immutable rate version.'],
                    'effective_to' => ['Choose a non-overlapping period; active rate history is never rewritten during activation.'],
                ]);
            }
            $this->assertNoUsageCrossesRateBoundaries($version);

            $version->status = RentalRateVersionStatus::Active;
            $version->approved_by = $userId;
            $version->approved_at = now();
            $version->row_version = $expectedVersion + 1;
            $version->updated_by = $userId;
            $version->save();

            return $version->refresh()->load('components');
        }, 3);
    }

    public function resolve(RentalAgreement $agreement, string $at): RentalAgreementRateVersion
    {
        return $agreement->rateVersions()
            ->whereIn('status', [
                RentalRateVersionStatus::Active->value,
                RentalRateVersionStatus::Superseded->value,
            ])
            ->where('effective_from', '<=', $at)
            ->where(function ($query) use ($at): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>', $at);
            })
            ->with('components')
            ->latest('version_number')
            ->firstOrFail();
    }

    public function assertSingleVersionCoversPeriod(
        RentalAgreement $agreement,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): void {
        if (! $end->greaterThan($start)) {
            throw new InvalidArgumentException('Usage finish time must be after start time.');
        }

        $versions = $agreement->rateVersions()
            ->whereIn('status', [
                RentalRateVersionStatus::Active->value,
                RentalRateVersionStatus::Superseded->value,
            ])
            ->where('effective_from', '<', $end->toDateTimeString())
            ->where(fn (Builder $query) => $query
                ->whereNull('effective_to')
                ->orWhere('effective_to', '>', $start->toDateTimeString()))
            ->lockForUpdate()
            ->get();
        $coveringVersions = $versions->filter(fn (RentalAgreementRateVersion $version): bool => (
            CarbonImmutable::parse($version->effective_from)->lessThanOrEqualTo($start)
            && (
                $version->effective_to === null
                || CarbonImmutable::parse($version->effective_to)->greaterThanOrEqualTo($end)
            )
        ));

        if ($versions->count() !== 1 || $coveringVersions->count() !== 1) {
            throw new InvalidArgumentException(
                'Usage period must stay inside one active rental rate version. Split the running-chart entry at the rate effective time.',
            );
        }
    }

    private function assertExpectedVersion(RentalAgreementRateVersion $version, int $expectedVersion): void
    {
        if ((int) $version->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental rate version changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertAgreementExpectedVersion(RentalAgreement $agreement, int $expectedVersion): void
    {
        if ((int) $agreement->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_agreement_version' => ['The rental agreement changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertAgreementAllowsRateChanges(RentalAgreement $agreement): void
    {
        if (in_array($agreement->status, [
            RentalAgreementStatus::Completed,
            RentalAgreementStatus::Terminated,
            RentalAgreementStatus::Cancelled,
        ], true)) {
            throw new InvalidArgumentException('Terminal rental agreements cannot receive or activate rate versions.');
        }
    }

    private function assertNoUsageCrossesRateBoundaries(RentalAgreementRateVersion $version): void
    {
        foreach ([
            'effective_from' => $version->effective_from,
            'effective_to' => $version->effective_to,
        ] as $field => $boundary) {
            if ($boundary === null) {
                continue;
            }

            $boundaryAt = CarbonImmutable::parse($boundary)->toDateTimeString();
            $usageCrossesBoundary = RentalUsageContext::query()
                ->where('tenant_id', $version->tenant_id)
                ->where('agreement_id', $version->agreement_id)
                ->whereHas('usageLog', fn (Builder $query): Builder => $query
                    ->whereNot('status', RentalUsageStatus::Reversed->value)
                    ->where('started_at', '<', $boundaryAt)
                    ->where('ended_at', '>', $boundaryAt))
                ->lockForUpdate()
                ->exists();

            if ($usageCrossesBoundary) {
                throw ValidationException::withMessages([
                    $field => ['Rate activation would split existing running-chart usage. Reverse or split those entries before activating this rate version.'],
                ]);
            }
        }
    }

    private function assertSupportedComponentUnit(RentalRateComponentCode $component, RentalRateUnit $unit): void
    {
        $supported = match ($component) {
            RentalRateComponentCode::DriverSalary => [
                RentalRateUnit::Fixed,
                RentalRateUnit::Month,
                RentalRateUnit::Week,
                RentalRateUnit::Day,
                RentalRateUnit::Hour,
                RentalRateUnit::Minute,
                RentalRateUnit::Trip,
                RentalRateUnit::Count,
            ],
            RentalRateComponentCode::NormalOvertime,
            RentalRateComponentCode::DoubleOvertime,
            RentalRateComponentCode::TripleOvertime => [
                RentalRateUnit::Hour,
                RentalRateUnit::Minute,
            ],
            default => null,
        };

        if ($supported !== null && ! in_array($unit, $supported, true)) {
            throw new InvalidArgumentException(sprintf(
                '%s does not support %s rates.',
                ucwords(str_replace('_', ' ', $component->value)),
                $unit->value,
            ));
        }
    }
}
