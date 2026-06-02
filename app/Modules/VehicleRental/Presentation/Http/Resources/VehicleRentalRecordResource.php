<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\DTO\DataRecord;

final class VehicleRentalRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof DataRecord) {
            return $this->withReadableLabels($this->resource->toArray());
        }

        if (is_array($this->resource)) {
            if (array_is_list($this->resource)) {
                return array_map(
                    fn (mixed $row): mixed => is_array($row) ? $this->withReadableLabels($row) : $row,
                    $this->resource,
                );
            }

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
        if ($tenantId < 1) {
            return $payload;
        }

        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : 0;
        if ($customerId > 0) {
            $customer = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->first();
            if ($customer !== null) {
                $code = (string) ($customer->customer_code ?? $customer->code ?? '');
                $name = (string) ($customer->display_name ?? $customer->customer_name ?? $customer->name ?? '');
                $payload['lessee'] = ['id' => $customerId, 'code' => $code, 'name' => $name, 'display_name' => trim($code . ' - ' . $name, ' -')];
                $payload['customer_label'] = $payload['lessee']['display_name'];
            }
        }

        $providerId = isset($payload['provider_id']) ? (int) $payload['provider_id'] : 0;
        if ($providerId > 0) {
            $supplier = DB::table('suppliers')->where('tenant_id', $tenantId)->where('id', $providerId)->first();
            if ($supplier !== null) {
                $code = (string) ($supplier->supplier_code ?? $supplier->code ?? '');
                $name = (string) ($supplier->display_name ?? $supplier->supplier_name ?? $supplier->name ?? '');
                $payload['lessor'] = ['id' => $providerId, 'type' => 'supplier', 'code' => $code, 'name' => $name, 'display_name' => trim($code . ' - ' . $name, ' -')];
                $payload['provider_label'] = $payload['lessor']['display_name'];
            }
        } elseif (($payload['lessor_party_name'] ?? null) !== null) {
            $payload['lessor'] = [
                'id' => $payload['lessor_party_id'] ?? null,
                'type' => $payload['lessor_party_type'] ?? 'external_party',
                'code' => null,
                'name' => $payload['lessor_party_name'],
                'display_name' => $payload['lessor_party_name'],
            ];
            $payload['provider_label'] = $payload['lessor_party_name'];
        }

        $rentalVehicleId = isset($payload['rental_vehicle_id']) ? (int) $payload['rental_vehicle_id'] : 0;
        if ($rentalVehicleId > 0) {
            $rentalVehicle = DB::table('vehicle_rental_vehicles')->where('tenant_id', $tenantId)->where('id', $rentalVehicleId)->first();
            if ($rentalVehicle !== null) {
                $vehicle = null;
                if (($rentalVehicle->vehicle_id ?? null) !== null) {
                    $vehicle = DB::table('vehicles')->where('tenant_id', $tenantId)->where('id', (int) $rentalVehicle->vehicle_id)->first();
                }
                $registration = (string) ($vehicle->registration_number ?? $vehicle->plate_number ?? $rentalVehicle->registration_number ?? $rentalVehicle->vehicle_number ?? '');
                $name = trim((string) ($vehicle->make ?? '') . ' ' . (string) ($vehicle->model ?? ''));
                $payload['vehicle'] = ['id' => $rentalVehicleId, 'registration_number' => $registration, 'display_name' => trim($registration . ' - ' . $name, ' -')];
                $payload['vehicle_label'] = $payload['vehicle']['display_name'];
            }
        }

        foreach (['lessee_agreement_id', 'lessor_agreement_id', 'parent_agreement_id', 'agreement_id'] as $column) {
            $agreementId = isset($payload[$column]) ? (int) $payload[$column] : 0;
            if ($agreementId < 1) {
                continue;
            }
            $agreement = DB::table('vehicle_rental_agreements')->where('tenant_id', $tenantId)->where('id', $agreementId)->first();
            if ($agreement !== null) {
                $payload[$column . '_label'] = (string) $agreement->agreement_number;
            }
        }

        if (isset($payload['running_charts']) && is_array($payload['running_charts'])) {
            $payload['running_charts'] = array_map(
                fn (mixed $row): mixed => is_array($row) ? $this->withReadableLabels($row) : $row,
                $payload['running_charts'],
            );
        }

        return $payload;
    }
}
