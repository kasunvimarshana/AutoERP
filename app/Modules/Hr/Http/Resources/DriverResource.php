<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

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

        $licenseIssued = $primaryLicenseAssignment?->issued_date
            ? $primaryLicenseAssignment->issued_date->toDateString()
            : ($metadata['driving_license_issued'] ?? null);

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

        return [
            'id' => $this->getKey(),
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'driver_number' => $this->employee_number,
            'employee_number' => $this->employee_number,
            'code' => $this->code,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'name' => $this->display_name,
            'nic_passport' => $metadata['nic_passport'] ?? $metadata['national_id'] ?? null,
            'mobile' => $this->mobile,
            'phone' => $this->phone,
            'email' => $this->email,
            'gender' => $this->value($this->gender),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'joined_date' => $this->joined_date?->toDateString(),
            'resigned_date' => $this->resigned_date?->toDateString(),
            'driving_license_number' => $licenseNumber,
            'driving_license_classes' => $metadata['driving_license_classes'] ?? [],
            'driving_license_issued' => $licenseIssued,
            'driving_license_expiry' => $licenseExpiry,
            'license_status' => $licenseStatus,
            'days_until_license_expiry' => $daysUntilExpiry,
            'emergency_contact_name' => $metadata['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $metadata['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $metadata['emergency_contact_relationship'] ?? null,
            'address' => $metadata['address'] ?? null,
            'city' => $metadata['city'] ?? null,
            'blood_group' => $metadata['blood_group'] ?? null,
            'work_start_time' => $metadata['work_start_time'] ?? null,
            'work_end_time' => $metadata['work_end_time'] ?? null,
            'standard_shift_hours' => isset($metadata['standard_shift_hours']) && $metadata['standard_shift_hours'] !== null
                ? (float) $metadata['standard_shift_hours']
                : null,
            'default_daily_rate' => (string) $this->default_daily_rate,
            'default_hourly_rate' => (string) $this->default_hourly_rate,
            'notes' => $this->notes,
            'status' => $this->value($this->status),
            'availability_status' => $this->value($this->availability_status),
            'current_assignment' => $this->current_assignment ?? null,
            'recent_assignments' => $this->recent_assignments ?? [],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
