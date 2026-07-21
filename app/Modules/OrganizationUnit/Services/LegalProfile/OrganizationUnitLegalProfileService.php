<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\LegalProfile;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\OrganizationUnit\Models\OrganizationUnitLegalProfileModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\OrganizationUnit\Services\Audit\OrganizationUnitAuditService;

final class OrganizationUnitLegalProfileService
{
    public function __construct(private readonly OrganizationUnitAuditService $audit) {}

    public function find(int $tenantId, int $organizationUnitId): ?OrganizationUnitLegalProfileModel
    {
        $this->organizationUnit($tenantId, $organizationUnitId);

        return OrganizationUnitLegalProfileModel::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->first();
    }

    /** @param array<string, mixed> $payload */
    public function upsert(int $tenantId, int $organizationUnitId, array $payload): OrganizationUnitLegalProfileModel
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $payload): OrganizationUnitLegalProfileModel {
            $this->organizationUnit($tenantId, $organizationUnitId, lock: true);
            $profile = OrganizationUnitLegalProfileModel::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $organizationUnitId)
                ->lockForUpdate()
                ->first();
            $expectedVersion = isset($payload['expected_version']) ? (int) $payload['expected_version'] : null;

            if ($profile instanceof OrganizationUnitLegalProfileModel) {
                if ($expectedVersion === null || $expectedVersion !== (int) $profile->row_version) {
                    throw ValidationException::withMessages([
                        'expected_version' => ['Organization legal profile was changed by another request. Reload it before saving.'],
                    ]);
                }
            } elseif ($expectedVersion !== null) {
                throw ValidationException::withMessages([
                    'expected_version' => ['Expected version must be omitted when creating the organization legal profile.'],
                ]);
            } else {
                $profile = new OrganizationUnitLegalProfileModel();
                $profile->tenant_id = $tenantId;
                $profile->organization_unit_id = $organizationUnitId;
            }

            $before = $profile->exists ? $profile->attributesToArray() : null;
            $profile->forceFill([
                'legal_name' => $this->requiredString($payload['legal_name'] ?? null),
                'tin' => $this->nullableString($payload['tin'] ?? null),
                'vat_registration_number' => $this->nullableString($payload['vat_registration_number'] ?? null),
                'svat_registration_number' => $this->nullableString($payload['svat_registration_number'] ?? null),
                'address_line_1' => $this->requiredString($payload['address_line_1'] ?? null),
                'address_line_2' => $this->nullableString($payload['address_line_2'] ?? null),
                'city' => $this->nullableString($payload['city'] ?? null),
                'state' => $this->nullableString($payload['state'] ?? null),
                'postal_code' => $this->nullableString($payload['postal_code'] ?? null),
                'country' => $this->nullableString($payload['country'] ?? null),
                'phone' => $this->nullableString($payload['phone'] ?? null),
                'email' => $this->nullableString($payload['email'] ?? null),
            ]);
            $profile->save();
            $this->audit->legalProfile($profile->wasRecentlyCreated ? 'created' : 'updated', $profile, $before, $profile->attributesToArray());

            return $profile->refresh();
        }, 3);
    }

    private function organizationUnit(int $tenantId, int $organizationUnitId, bool $lock = false): OrganizationUnitModel
    {
        $query = OrganizationUnitModel::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($organizationUnitId);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function requiredString(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw ValidationException::withMessages(['legal_profile' => ['Required legal profile values cannot be blank.']]);
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
