<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\LegalProfile;

use Modules\OrganizationUnit\Contracts\OrganizationUnitLegalProfileReaderInterface;
use Modules\OrganizationUnit\DTOs\OrganizationUnitLegalProfileSnapshot;
use Modules\OrganizationUnit\Models\OrganizationUnitLegalProfileModel;

final class OrganizationUnitLegalProfileReader implements OrganizationUnitLegalProfileReaderInterface
{
    public function find(int $tenantId, int $organizationUnitId): ?OrganizationUnitLegalProfileSnapshot
    {
        $profile = OrganizationUnitLegalProfileModel::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->first();

        if (! $profile instanceof OrganizationUnitLegalProfileModel) {
            return null;
        }

        return new OrganizationUnitLegalProfileSnapshot(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            legalName: (string) $profile->legal_name,
            tin: $this->nullableString($profile->tin),
            vatRegistrationNumber: $this->nullableString($profile->vat_registration_number),
            svatRegistrationNumber: $this->nullableString($profile->svat_registration_number),
            address: $this->address($profile),
            phone: $this->nullableString($profile->phone),
            email: $this->nullableString($profile->email),
        );
    }

    private function address(OrganizationUnitLegalProfileModel $profile): string
    {
        return implode(', ', array_filter([
            $this->nullableString($profile->address_line_1),
            $this->nullableString($profile->address_line_2),
            $this->nullableString($profile->city),
            $this->nullableString($profile->state),
            $this->nullableString($profile->postal_code),
            $this->nullableString($profile->country),
        ]));
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
