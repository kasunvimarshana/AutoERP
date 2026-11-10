<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Services\EmployeeQueryService;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\RunningChartFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AirConditioningMode;
use Modules\VehicleRental\Enums\DriverIdentitySource;

final class RunningChartValidation
{
    public function __construct(
        private readonly EmployeeQueryService $employees,
        private readonly RentalCalendar $calendar,
    ) {}

    public function validate(array $input, AgreementContext $context): array
    {
        foreach (RunningChartFields::MUTABLE as $field) {
            if ($field === 'starts_at' || $field === 'ends_at') {
                continue;
            }
            if (array_key_exists($field, $input)) {
                if (is_string($input[$field]) && trim($input[$field]) === '') {
                    $input[$field] = null;
                }
            }
        }

        $rules = [
            'reference' => ['required', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'starts_at' => ['required', 'string'],
            'ends_at' => ['required', 'string'],
            'ac_mode' => ['nullable', Rule::enum(AirConditioningMode::class)],
            'driver_identity_source' => ['nullable', Rule::enum(DriverIdentitySource::class)],
            'driver_employee_id' => ['nullable', 'integer', 'min:1'],
            'driver_name_snapshot' => ['nullable', 'string', 'max:'.RunningChartFields::DRIVER_REFERENCE_LENGTH],
            'driver_reference_snapshot' => ['nullable', 'string', 'max:'.RunningChartFields::DRIVER_REFERENCE_LENGTH],
            'driver_observation' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
            'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
        ];
        foreach (RunningChartFields::DISTANCES as $field) {
            $rules[$field] = ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN];
            if (isset($input[$field]) && (is_int($input[$field]) || is_float($input[$field]))) {
                $input[$field] = (string) $input[$field];
            }
        }
        foreach (RunningChartFields::COUNTS as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:'.RunningChartFields::MAX_COUNT];
        }
        $input['reference'] = is_string($input['reference'] ?? null) ? trim($input['reference']) : ($input['reference'] ?? null);
        if ($input['reference'] === '') {
            $input['reference'] = null;
        }

        $data = array_replace(array_fill_keys(RunningChartFields::MUTABLE, null), Validator::make($input, $rules)->validate());
        foreach (['driver_observation', 'notes', 'driver_name_snapshot', 'driver_reference_snapshot'] as $strField) {
            if (isset($data[$strField]) && is_string($data[$strField])) {
                $trimmed = trim($data[$strField]);
                $data[$strField] = $trimmed === '' ? null : $trimmed;
            }
        }
        $this->driverIdentity($data, $context);

        $timezone = $this->calendar->timezone($context);
        $start = OperationalTime::parse($data['starts_at'], 'starts_at')->setTimezone($timezone);
        $end = OperationalTime::parse($data['ends_at'], 'ends_at')->setTimezone($timezone);

        if ($end <= $start) {
            throw ValidationException::withMessages(['ends_at' => ['Usage end must follow usage start.']]);
        }
        $data['starts_at_input'] = $data['starts_at'];
        $data['ends_at_input'] = $data['ends_at'];
        $data['starts_at'] = OperationalTime::database($start);
        $data['ends_at'] = OperationalTime::database($end);
        foreach (RunningChartFields::DISTANCES as $field) {
            if ($data[$field] !== null && $data[$field] !== '') {
                $data[$field] = bcadd((string) $data[$field], AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE);
            } else {
                $data[$field] = null;
            }
        }
        if ($data['start_odometer'] !== null && $data['end_odometer'] !== null) {
            $total = bcsub($data['end_odometer'], $data['start_odometer'], AgreementFields::DECIMAL_SCALE);
            if (bccomp($total, AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE) < 0) {
                throw ValidationException::withMessages(['end_odometer' => ['End odometer cannot be lower than start odometer.']]);
            }
            foreach (['garage_km', 'commercial_km'] as $field) {
                if ($data[$field] !== null && bccomp($data[$field], $total, AgreementFields::DECIMAL_SCALE) > 0) {
                    throw ValidationException::withMessages([$field => ['Recorded distance cannot exceed total physical distance.']]);
                }
            }
            if ($data['garage_km'] !== null && $data['commercial_km'] !== null && bccomp(bcadd($data['garage_km'], $data['commercial_km'], AgreementFields::DECIMAL_SCALE), $total, AgreementFields::DECIMAL_SCALE) > 0) {
                throw ValidationException::withMessages(['commercial_km' => ['Combined commercial and garage distance cannot exceed total physical distance.']]);
            }
        }

        return $data;
    }

    private function driverIdentity(array &$data, AgreementContext $context): void
    {
        $source = $data['driver_identity_source'] === null ? null : DriverIdentitySource::from($data['driver_identity_source']);
        if ($source === null) {
            if ($data['driver_employee_id'] !== null || $data['driver_name_snapshot'] !== null || $data['driver_reference_snapshot'] !== null) {
                throw ValidationException::withMessages(['driver_identity_source' => ['Select the driver identity source before recording driver identity.']]);
            }

            return;
        }

        if ($source === DriverIdentitySource::Employee) {
            if ($data['driver_employee_id'] === null) {
                throw ValidationException::withMessages(['driver_employee_id' => ['Select the employee who drove this usage.']]);
            }
            try {
                $employee = $this->employees->employee((int) $data['driver_employee_id'], $context->tenantId, $context->organizationUnitId);
            } catch (ModelNotFoundException) {
                throw ValidationException::withMessages(['driver_employee_id' => ['Select an employee available to this organization.']]);
            }
            $data['driver_name_snapshot'] = $employee->display_name;
            $data['driver_reference_snapshot'] = $employee->employee_number;

            return;
        }

        if ($data['driver_employee_id'] !== null) {
            throw ValidationException::withMessages(['driver_employee_id' => ['External driver identity cannot reference an HR employee.']]);
        }
        $name = trim((string) ($data['driver_name_snapshot'] ?? ''));
        $reference = trim((string) ($data['driver_reference_snapshot'] ?? ''));
        if ($name === '' || $reference === '') {
            throw ValidationException::withMessages(['driver_reference_snapshot' => ['Record both the external driver name and a stable reference.']]);
        }
        $data['driver_name_snapshot'] = $name;
        $data['driver_reference_snapshot'] = mb_strtoupper($reference);
    }
}
