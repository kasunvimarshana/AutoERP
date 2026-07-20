<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalRateLineData;
use Modules\VehicleRental\DTOs\RentalRateVersionData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalRateLine;
use Modules\VehicleRental\Models\RentalRateVersion;
use Modules\VehicleRental\Services\Validation\RentalAgreementValidator;
use Modules\VehicleRental\Services\Validation\RentalRatePolicy;

final class RentalAgreementService
{
    private const INITIAL_VERSION = 1;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalAgreementValidator $validator,
        private readonly RentalRatePolicy $ratePolicy,
    ) {}

    public function create(RentalAgreementData $data): RentalAgreement
    {
        $this->validator->validateData($data);

        return DB::transaction(function () use ($data): RentalAgreement {
            $this->validator->assertActiveReferences(
                $data->kind,
                $data->tenantId,
                $data->organizationUnitId,
                $data->customerId,
                $data->supplierId,
                $data->currencyId,
                $data->taxGroupId,
            );

            $agreement = new RentalAgreement();
            $agreement->forceFill($this->attributes(
                $data,
                $data->agreementNumber ?? $this->numbers->agreement($data->tenantId, $data->organizationUnitId),
            ));
            $agreement->save();

            $version = new RentalRateVersion();
            $version->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'agreement_id' => $agreement->getKey(),
                'version_number' => self::INITIAL_VERSION,
                'effective_from' => $data->startsOn,
                'effective_to' => $data->endsOn,
                'status' => RentalRateVersionStatus::Draft->value,
                'created_by' => $data->actorId,
            ])->save();
            $this->replaceRateLines($version, $data->rates);

            return $this->load($agreement);
        });
    }

    public function update(RentalAgreement $agreement, RentalAgreementData $data, int $expectedVersion): RentalAgreement
    {
        $this->validator->validateData($data);

        return DB::transaction(function () use ($agreement, $data, $expectedVersion): RentalAgreement {
            $this->validator->assertActiveReferences(
                $data->kind,
                $data->tenantId,
                $data->organizationUnitId,
                $data->customerId,
                $data->supplierId,
                $data->currencyId,
                $data->taxGroupId,
            );

            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft rental agreements can be edited. Create a successor rate version for active agreements.');
            }
            if ($agreement->kind !== $data->kind) {
                throw new InvalidArgumentException('Rental agreement kind cannot be changed after creation.');
            }

            $attributes = $this->attributes($data, (string) $agreement->agreement_number);
            unset($attributes['created_by']);
            $agreement->forceFill([...$attributes, 'row_version' => $expectedVersion + 1])->save();

            $version = $agreement->rateVersions()->lockForUpdate()->firstOrFail();
            if ($version->status !== RentalRateVersionStatus::Draft) {
                throw new InvalidArgumentException('The editable rate version is no longer draft.');
            }
            $version->forceFill([
                'effective_from' => $data->startsOn,
                'effective_to' => $data->endsOn,
                'row_version' => (int) $version->row_version + 1,
            ])->save();
            $this->replaceRateLines($version, $data->rates);

            return $this->load($agreement);
        });
    }

    public function deleteDraft(RentalAgreement $agreement, int $expectedVersion): void
    {
        DB::transaction(function () use ($agreement, $expectedVersion): void {
            $organizationUnitId = $agreement->organization_unit_id === null
                ? null
                : (int) $agreement->organization_unit_id;
            $agreement = RentalAgreement::query()
                ->forContext((int) $agreement->tenant_id, $organizationUnitId)
                ->whereKey($agreement->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft rental agreements can be deleted. Active and closed agreements must be retained as history.');
            }
            if ($agreement->assignments()->lockForUpdate()->exists()
                || $agreement->calculations()->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('A rental agreement with operational or financial history cannot be deleted.');
            }

            $rateVersions = $agreement->rateVersions()->lockForUpdate()->get();
            foreach ($rateVersions as $rateVersion) {
                $rateVersion->delete();
            }
            $agreement->delete();
        });
    }

    public function activate(RentalAgreement $agreement, int $expectedVersion, ?int $actorId): RentalAgreement
    {
        return DB::transaction(function () use ($agreement, $expectedVersion, $actorId): RentalAgreement {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft rental agreements can be activated.');
            }
            if ($agreement->executed_at === null) {
                throw new InvalidArgumentException('An execution date is required before activating a rental agreement.');
            }
            $this->assertActiveAgreementReferences($agreement);

            $version = $agreement->rateVersions()->with('lines')->lockForUpdate()->firstOrFail();
            $this->ratePolicy->assertRequiredBaseRate($agreement, $version);
            $version->forceFill([
                'status' => RentalRateVersionStatus::Active->value,
                'activated_by' => $actorId,
                'activated_at' => now(),
                'row_version' => (int) $version->row_version + 1,
            ])->save();

            $agreement->forceFill([
                'status' => RentalAgreementStatus::Active->value,
                'activated_by' => $actorId,
                'activated_at' => now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($agreement);
        });
    }

    public function createSuccessorRateVersion(
        RentalAgreement $agreement,
        RentalRateVersionData $data,
        int $expectedVersion,
    ): RentalAgreement {
        return DB::transaction(function () use ($agreement, $data, $expectedVersion): RentalAgreement {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Active) {
                throw new InvalidArgumentException('Successor rates can be created only for an active rental agreement.');
            }
            $this->assertActiveAgreementReferences($agreement);

            $effectiveFrom = CarbonImmutable::parse($data->effectiveFrom)->startOfDay();
            if ($effectiveFrom->lte($agreement->starts_on->startOfDay())) {
                throw new InvalidArgumentException('Successor rates must start after the agreement start date.');
            }
            if ($agreement->ends_on !== null && $effectiveFrom->gt($agreement->ends_on->startOfDay())) {
                throw new InvalidArgumentException('Successor rates must start within the agreement period.');
            }

            $latest = $agreement->rateVersions()
                ->with('lines')
                ->reorder('version_number')
                ->lockForUpdate()
                ->get()
                ->last();
            if (! $latest instanceof RentalRateVersion || $latest->status !== RentalRateVersionStatus::Active) {
                throw new InvalidArgumentException('The latest agreement rate version must be active before creating a successor.');
            }
            if ($effectiveFrom->lte($latest->effective_from->startOfDay())) {
                throw new InvalidArgumentException('Successor rates must start after the latest rate version.');
            }

            $this->ratePolicy->validate($agreement->kind, $agreement->billing_basis, $data->rates);
            $latest->forceFill([
                'effective_to' => $effectiveFrom->subDay()->toDateString(),
                'row_version' => (int) $latest->row_version + 1,
            ])->save();

            $successor = new RentalRateVersion();
            $successor->forceFill([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'version_number' => (int) $latest->version_number + 1,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $agreement->ends_on?->toDateString(),
                'status' => RentalRateVersionStatus::Active->value,
                'created_by' => $data->actorId,
                'activated_by' => $data->actorId,
                'activated_at' => now(),
            ])->save();
            $this->replaceRateLines($successor, $data->rates);
            $this->ratePolicy->assertRequiredBaseRate($agreement, $successor->load('lines'));

            $agreement->forceFill(['row_version' => $expectedVersion + 1])->save();

            return $this->load($agreement);
        });
    }

    public function close(RentalAgreement $agreement, int $expectedVersion, ?int $actorId): RentalAgreement
    {
        return DB::transaction(function () use ($agreement, $expectedVersion, $actorId): RentalAgreement {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Active) {
                throw new InvalidArgumentException('Only active rental agreements can be closed.');
            }
            if ($agreement->assignments()
                ->whereIn('status', [RentalAssignmentStatus::Planned->value, RentalAssignmentStatus::Active->value])
                ->lockForUpdate()
                ->exists()) {
                throw new InvalidArgumentException('Close or cancel all active rental assignments before closing the agreement.');
            }

            $agreement->forceFill([
                'status' => RentalAgreementStatus::Closed->value,
                'closed_by' => $actorId,
                'closed_at' => now(),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($agreement);
        });
    }

    public function relations(): array
    {
        return ['customer', 'supplier', 'currency', 'taxGroup', 'rateVersions.lines', 'assignments'];
    }

    private function assertActiveAgreementReferences(RentalAgreement $agreement): void
    {
        $this->validator->assertActiveReferences(
            $agreement->kind,
            (int) $agreement->tenant_id,
            $agreement->organization_unit_id === null ? null : (int) $agreement->organization_unit_id,
            $agreement->customer_id === null ? null : (int) $agreement->customer_id,
            $agreement->supplier_id === null ? null : (int) $agreement->supplier_id,
            (int) $agreement->currency_id,
            $agreement->tax_group_id === null ? null : (int) $agreement->tax_group_id,
        );
    }

    private function attributes(RentalAgreementData $data, string $number): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'agreement_number' => $number,
            'kind' => $data->kind->value,
            'customer_id' => $data->customerId,
            'supplier_id' => $data->supplierId,
            'executed_at' => $data->executedAt,
            'starts_on' => $data->startsOn,
            'ends_on' => $data->endsOn,
            'billing_basis' => $data->billingBasis->value,
            'currency_id' => $data->currencyId,
            'tax_group_id' => $data->taxGroupId,
            'included_km' => $this->math->normalize($data->includedKm),
            'deposit_required' => $data->depositRequired,
            'deposit_amount' => $this->math->normalize($data->depositAmount),
            'payment_terms_days' => $data->paymentTermsDays,
            'terms' => $data->terms,
            'notes' => $data->notes,
            'created_by' => $data->actorId,
        ];
    }

    /** @param list<RentalRateLineData> $rates */
    private function replaceRateLines(RentalRateVersion $version, array $rates): void
    {
        $version->lines()->delete();
        foreach (array_values($rates) as $index => $rate) {
            $line = new RentalRateLine();
            $line->forceFill([
                'tenant_id' => $version->tenant_id,
                'organization_unit_id' => $version->organization_unit_id,
                'rate_version_id' => $version->getKey(),
                'line_number' => $index + 1,
                'rate_code' => $rate->code->value,
                'unit' => $rate->unit->value,
                'rate' => $this->math->normalize($rate->rate),
                'is_taxable' => $rate->isTaxable,
                'description' => $rate->description,
            ])->save();
        }
    }

    private function assertExpectedVersion(RentalAgreement $agreement, int $expectedVersion): void
    {
        if ((int) $agreement->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Rental agreement was changed by another request. Reload it before continuing.');
        }
    }

    private function load(RentalAgreement $agreement): RentalAgreement
    {
        return $agreement->refresh()->load($this->relations());
    }
}
