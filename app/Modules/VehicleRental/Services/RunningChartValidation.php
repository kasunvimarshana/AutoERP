<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\RunningChartFields;
use Modules\VehicleRental\Enums\AirConditioningMode;

final class RunningChartValidation
{
    public function validate(array $input): array
    {
        $rules = ['reference' => ['required', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH], 'starts_at' => ['required', 'string'], 'ends_at' => ['required', 'string'], 'ac_mode' => ['nullable', Rule::enum(AirConditioningMode::class)], 'driver_observation' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH], 'notes' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH]];
        foreach (RunningChartFields::DISTANCES as $field) {
            $rules[$field] = ['nullable', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN];
        }
        foreach (RunningChartFields::COUNTS as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:'.RunningChartFields::MAX_COUNT];
        }
        $input['reference'] = is_string($input['reference'] ?? null) ? trim($input['reference']) : null;
        $data = array_replace(array_fill_keys(RunningChartFields::MUTABLE, null), Validator::make($input, $rules)->validate());
        $start = OperationalTime::parse($data['starts_at'], 'starts_at');
        $end = OperationalTime::parse($data['ends_at'], 'ends_at');
        if ($end <= $start || $end->isFuture()) {
            throw ValidationException::withMessages(['ends_at' => ['Record completed usage ending after its start, not in the future.']]);
        }
        $data['starts_at_input'] = $data['starts_at'];
        $data['ends_at_input'] = $data['ends_at'];
        $data['starts_at'] = OperationalTime::database($start);
        $data['ends_at'] = OperationalTime::database($end);
        foreach (RunningChartFields::DISTANCES as $field) {
            if ($data[$field] !== null) {
                $data[$field] = bcadd($data[$field], AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE);
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
        }

        return $data;
    }
}
