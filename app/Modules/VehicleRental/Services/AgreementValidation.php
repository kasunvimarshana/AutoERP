<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Models\Customer;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Supplier\Models\Supplier;
use Modules\User\Models\UserModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalBasis;

final class AgreementValidation
{
    public function validate(array $input, AgreementKind $kind, AgreementContext $context): array
    {
        $rules = [
            'reference' => ['required', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'party_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => [$kind === AgreementKind::Owner ? 'required' : 'prohibited', 'integer', 'min:1'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'agreed_on' => ['required', 'date_format:Y-m-d'], 'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'basis' => ['required', Rule::enum(RentalBasis::class)], 'driver_mode' => ['required', Rule::enum(DriverMode::class)],
            'terms' => ['present', 'array:'.implode(',', AgreementFields::AMOUNTS)],
            'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
        ];
        foreach (AgreementFields::AMOUNTS as $field) {
            $rules['terms.'.$field] = ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN];
        }
        $input['reference'] = is_string($input['reference'] ?? null) ? trim($input['reference']) : $input['reference'] ?? null;
        $data = Validator::make($input, $rules)->validate();
        $this->assertContext($context);
        // Snapshot canonical labels; never accept the client's display name as identity evidence.
        if ($kind === AgreementKind::Owner) {
            $vehicle = $this->visible(Vehicle::query(), $context)->whereKey($data['vehicle_id'])->lockForUpdate()->first();
            if ($vehicle === null) {
                throw ValidationException::withMessages(['vehicle_id' => ['Select a vehicle available in this organization.']]);
            }
            $data['vehicle_number_snapshot'] = $vehicle->vehicle_number;
            $data['vehicle_registration_snapshot'] = $vehicle->registration_number;
        }
        $partyQuery = $kind === AgreementKind::Customer ? Customer::query() : Supplier::query();
        $party = $this->visible($partyQuery, $context)->whereKey($data['party_id'])->lockForUpdate()->first();
        if ($party === null) {
            throw ValidationException::withMessages(['party_id' => ['Select a party available in this organization.']]);
        }
        $currency = CurrencyModel::query()->whereKey($data['currency_id'])->where('is_active', true)->first();
        if ($currency === null) {
            throw ValidationException::withMessages(['currency_id' => ['Select an active currency.']]);
        }
        $data['party_name_snapshot'] = $party->name;
        $data['party_code_snapshot'] = $party->code;
        $data['currency_code_snapshot'] = $currency->code;
        $data['terms'] = array_replace(array_fill_keys(AgreementFields::AMOUNTS, null), $data['terms']);
        foreach ($data['terms'] as $field => $amount) {
            if ($amount !== null) {
                $data['terms'][$field] = bcadd($amount, AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE);
            }
        }
        $data[$kind === AgreementKind::Customer ? 'customer_id' : 'supplier_id'] = (int) $data['party_id'];
        unset($data['party_id']);
        $data['ends_on'] = $data['ends_on'] ?? null;
        $data['notes'] = $data['notes'] ?? null;

        return $data;
    }

    public function assertContext(AgreementContext $context): void
    {
        if (! OrganizationUnitModel::query()->forTenant($context->tenantId)->whereKey($context->organizationUnitId)->exists()
            || ! UserModel::query()->where('tenant_id', $context->tenantId)->whereKey($context->actorId)->exists()) {
            throw ValidationException::withMessages(['context' => ['A valid tenant, organization and actor context is required.']]);
        }
    }

    private function visible(Builder $query, AgreementContext $context): Builder
    {
        return $query->where('tenant_id', $context->tenantId)->where(fn (Builder $scope) => $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $context->organizationUnitId));
    }
}
