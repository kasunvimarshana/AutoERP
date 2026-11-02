<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DriverSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        // Primary driving license details
        $primaryLicenseAssignment = $this->relationLoaded('licenseAssignments')
            ? $this->licenseAssignments->first()
            : null;

        $licenseNumber = $primaryLicenseAssignment?->license_number
            ?? $metadata['driving_license_number']
            ?? $metadata['license_number']
            ?? null;

        $licenseExpiry = $primaryLicenseAssignment?->expiry_date
            ? $primaryLicenseAssignment->expiry_date->toDateString()
            : ($metadata['driving_license_expiry'] ?? null);

        $licenseStatus = 'valid';
        $daysUntilExpiry = null;
        if ($licenseExpiry !== null) {
            $expiryDate = CarbonImmutable::parse($licenseExpiry)->startOfDay();
            $today = CarbonImmutable::now()->startOfDay();
            $daysUntilExpiry = (int) $today->diffInDays($expiryDate, false);

            if ($daysUntilExpiry < 0) {
                $licenseStatus = 'expired';
            } elseif ($daysUntilExpiry <= 30) {
                $licenseStatus = 'expiring_soon';
            }
        }

        // Active vehicle rental assignment context (if passed via driver enrichment)
        $currentAssignment = $this->current_assignment ?? null;

        return [
            'id' => $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'driver_number' => $this->employee_number,
            'employee_number' => $this->employee_number,
            'code' => $this->code,
            'name' => $this->display_name,
            'display_name' => $this->display_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'nic_passport' => $metadata['nic_passport'] ?? $metadata['national_id'] ?? null,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'email' => $this->email,
            'driving_license_number' => $licenseNumber,
            'driving_license_classes' => $metadata['driving_license_classes'] ?? [],
            'driving_license_expiry' => $licenseExpiry,
            'license_status' => $licenseStatus,
            'days_until_license_expiry' => $daysUntilExpiry,
            'status' => $this->value($this->status),
            'availability_status' => $this->value($this->availability_status),
            'current_assignment' => $currentAssignment,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
