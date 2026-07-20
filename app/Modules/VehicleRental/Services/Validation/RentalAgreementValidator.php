<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Services\Lookups\RentalAgreementReferenceQuery;

final class RentalAgreementValidator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalRatePolicy $rates,
        private readonly RentalAgreementReferenceQuery $references,
    ) {}

    public function validateData(RentalAgreementData $data): void
    {
        if ($data->tenantId < 1) {
            throw new InvalidArgumentException('Rental agreement tenant is required.');
        }
        $startsOn = CarbonImmutable::parse($data->startsOn)->startOfDay();
        $endsOn = $data->endsOn === null ? null : CarbonImmutable::parse($data->endsOn)->startOfDay();
        if ($endsOn !== null && $endsOn->lt($startsOn)) {
            throw new InvalidArgumentException('Rental agreement end date cannot be before its start date.');
        }
        if ($this->math->isNegative($data->includedKm) || $this->math->isNegative($data->depositAmount)) {
            throw new InvalidArgumentException('Rental agreement kilometre and deposit amounts cannot be negative.');
        }
        if ($data->paymentTermsDays < 0) {
            throw new InvalidArgumentException('Rental agreement payment terms cannot be negative.');
        }

        $hasCustomer = $data->customerId !== null;
        $hasSupplier = $data->supplierId !== null;
        if ($data->kind === RentalAgreementKind::Customer && (! $hasCustomer || $hasSupplier)) {
            throw new InvalidArgumentException('Customer rental agreements require exactly one customer and no supplier.');
        }
        if ($data->kind === RentalAgreementKind::Owner && (! $hasSupplier || $hasCustomer)) {
            throw new InvalidArgumentException('Owner rental agreements require exactly one supplier and no customer.');
        }
        if ($data->kind === RentalAgreementKind::Owner && $data->depositRequired) {
            throw new InvalidArgumentException('Security deposits belong to customer agreements only.');
        }
        if (! $data->depositRequired && ! $this->math->isZero($data->depositAmount)) {
            throw new InvalidArgumentException('Deposit amount must be zero when a security deposit is not required.');
        }

        $this->rates->validate($data->kind, $data->billingBasis, $data->rates);
    }

    public function assertActiveReferences(
        RentalAgreementKind $kind,
        int $tenantId,
        ?int $organizationUnitId,
        ?int $customerId,
        ?int $supplierId,
        int $currencyId,
        ?int $taxGroupId,
    ): void {
        if ($kind === RentalAgreementKind::Customer) {
            Customer::query()
                ->forTenant($tenantId, $organizationUnitId)
                ->active()
                ->lockForUpdate()
                ->findOrFail($customerId);
        } else {
            Supplier::query()
                ->forTenant($tenantId, $organizationUnitId)
                ->active()
                ->lockForUpdate()
                ->findOrFail($supplierId);
        }

        CurrencyModel::query()
            ->whereKey($currencyId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();

        if ($taxGroupId !== null) {
            $this->references
                ->activeTaxGroups($tenantId, $organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($taxGroupId);
        }
    }
}
