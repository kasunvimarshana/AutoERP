<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;

final class VehicleServiceJobCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->withLabels($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            return $this->withLabels($this->resource);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withLabels(array $payload): array
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        if ($tenantId < 1) {
            return $payload;
        }

        $customerId = isset($payload['linked_customer_id']) ? (int) $payload['linked_customer_id'] : 0;
        if ($customerId > 0) {
            $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
            if ($customer !== null) {
                $name = (string) ($customer->display_name ?? $customer->customer_name ?? $customer->name ?? '');
                $code = (string) ($customer->code ?? $customer->customer_code ?? '');
                $payload['customer'] = ['id' => $customerId, 'code' => $code, 'name' => $name, 'display_name' => trim($code . ' - ' . $name, ' -')];
                $payload['customer_label'] = $payload['customer']['display_name'];
                $payload['linked_customer_name'] = $name;
            }
        }

        $vehicleId = isset($payload['vehicle_id']) ? (int) $payload['vehicle_id'] : 0;
        if ($vehicleId > 0) {
            $vehicle = DB::table('vehicles')->where('tenant_id', $tenantId)->where('id', $vehicleId)->first();
            if ($vehicle !== null) {
                $registration = (string) ($vehicle->registration_number ?? $vehicle->plate_number ?? '');
                $makeModel = trim((string) ($vehicle->make ?? '') . ' ' . (string) ($vehicle->model ?? ''));
                $payload['vehicle'] = ['id' => $vehicleId, 'registration_number' => $registration, 'display_name' => trim($registration . ' - ' . $makeModel, ' -')];
                $payload['vehicle_label'] = $payload['vehicle']['display_name'];
            }
        }

        $serviceTypeId = isset($payload['service_type_id']) ? (int) $payload['service_type_id'] : 0;
        if ($serviceTypeId > 0) {
            $serviceType = DB::table('vehicle_service_types')->where('tenant_id', $tenantId)->where('id', $serviceTypeId)->first();
            if ($serviceType !== null) {
                $payload['service_type'] = ['id' => $serviceTypeId, 'code' => $serviceType->code ?? null, 'name' => $serviceType->name ?? null];
                $payload['service_type_label'] = trim((string) ($serviceType->code ?? '') . ' - ' . (string) ($serviceType->name ?? ''), ' -');
            }
        }

        foreach (['assigned_to' => 'assigned_to_employee', 'created_by' => 'service_advisor'] as $column => $prefix) {
            $employeeId = isset($payload[$column]) ? (int) $payload[$column] : 0;
            if ($employeeId < 1) {
                continue;
            }

            $employee = DB::table('employees')->where('tenant_id', $tenantId)->where('id', $employeeId)->first();
            if ($employee === null) {
                continue;
            }

            $code = (string) ($employee->employee_no ?? $employee->employee_code ?? $employee->code ?? '');
            $name = (string) ($employee->full_name ?? $employee->display_name ?? $employee->name ?? '');
            $payload[$prefix] = ['id' => $employeeId, 'code' => $code, 'name' => $name, 'display_name' => trim($code . ' - ' . $name, ' -')];
            $payload[$prefix . '_label'] = $payload[$prefix]['display_name'];
        }

        return $payload;
    }
}
