<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;

final class VehicleServiceLaborAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->withReadableLabels($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            return $this->withReadableLabels($this->resource);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function withReadableLabels(array $payload): array
    {
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        $employeeId = isset($payload['employee_id']) ? (int) $payload['employee_id'] : 0;
        $laborItemId = isset($payload['labor_item_id']) ? (int) $payload['labor_item_id'] : 0;

        if ($tenantId > 0 && $employeeId > 0) {
            $employee = DB::table('employees')
                ->where('tenant_id', $tenantId)
                ->where('id', $employeeId)
                ->first();

            if ($employee !== null) {
                $code = trim((string) ($employee->employee_no ?? $employee->employee_code ?? $employee->code ?? ''));
                $name = trim((string) ($employee->full_name ?? $employee->display_name ?? $employee->name ?? ''));
                $payload['employee'] = [
                    'id' => $employeeId,
                    'employee_no' => $code,
                    'full_name' => $name,
                    'display_name' => trim($code . ' - ' . $name, ' -'),
                ];
                $payload['employee_label'] = $payload['employee']['display_name'];
            }
        }

        if ($tenantId > 0 && $laborItemId > 0) {
            $laborItem = DB::table('vehicle_service_labor_items')
                ->leftJoin('items', 'items.id', '=', 'vehicle_service_labor_items.item_id')
                ->where('vehicle_service_labor_items.tenant_id', $tenantId)
                ->where('vehicle_service_labor_items.id', $laborItemId)
                ->select([
                    'vehicle_service_labor_items.id',
                    'vehicle_service_labor_items.item_id',
                    'vehicle_service_labor_items.description',
                    'items.sku as item_code',
                    'items.name as item_name',
                ])
                ->first();

            if ($laborItem !== null) {
                $label = trim((string) ($laborItem->item_code ?? '') . ' - ' . (string) ($laborItem->item_name ?? ''), ' -');
                $payload['labor_item'] = [
                    'id' => $laborItemId,
                    'item_id' => $laborItem->item_id,
                    'item_code' => $laborItem->item_code,
                    'item_name' => $laborItem->item_name,
                    'description' => $laborItem->description,
                    'display_name' => $label !== '' ? $label : (string) $laborItem->description,
                ];
                $payload['labor_item_label'] = $payload['labor_item']['display_name'];
            }
        }

        return $payload;
    }
}
