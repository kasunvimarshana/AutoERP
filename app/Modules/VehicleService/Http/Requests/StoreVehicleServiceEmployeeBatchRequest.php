<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentBatchEntryData;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class StoreVehicleServiceEmployeeBatchRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    private const MAX_ASSIGNMENTS_PER_REQUEST = 100;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'lines' => ['required', 'array', 'min:1', 'max:'.self::MAX_ASSIGNMENTS_PER_REQUEST],
            'lines.*.line_id' => ['required', 'integer', 'min:1', 'distinct'],
            'lines.*.employee_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_ASSIGNMENTS_PER_REQUEST],
            'lines.*.employee_ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $count = collect($this->input('lines', []))
                ->sum(static fn (mixed $line): int => is_array($line) && is_array($line['employee_ids'] ?? null)
                    ? count($line['employee_ids'])
                    : 0);

            if ($count > self::MAX_ASSIGNMENTS_PER_REQUEST) {
                $validator->errors()->add(
                    'lines',
                    'A workforce batch cannot contain more than '.self::MAX_ASSIGNMENTS_PER_REQUEST.' employee assignments.',
                );
            }

            foreach ((array) $this->input('lines', []) as $lineIndex => $line) {
                if (! is_array($line) || ! is_array($line['employee_ids'] ?? null)) {
                    continue;
                }

                $seenEmployeeIds = [];
                foreach ($line['employee_ids'] as $employeeIndex => $employeeId) {
                    if (! is_numeric($employeeId)) {
                        continue;
                    }

                    $employeeId = (int) $employeeId;
                    if (isset($seenEmployeeIds[$employeeId])) {
                        $validator->errors()->add(
                            "lines.{$lineIndex}.employee_ids.{$employeeIndex}",
                            'An employee can only be selected once per workforce line.',
                        );
                    }
                    $seenEmployeeIds[$employeeId] = true;
                }
            }
        }];
    }

    /** @return list<VehicleServiceEmployeeAssignmentBatchEntryData> */
    public function toData(): array
    {
        $entries = [];
        foreach ($this->validated('lines') as $line) {
            foreach ($line['employee_ids'] as $employeeId) {
                $entries[] = new VehicleServiceEmployeeAssignmentBatchEntryData(
                    lineId: (int) $line['line_id'],
                    assignment: new VehicleServiceEmployeeAssignmentData(employeeId: (int) $employeeId),
                );
            }
        }

        return $entries;
    }
}
