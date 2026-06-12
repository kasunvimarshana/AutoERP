<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\EmployeeAddressType;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeDocumentStatus;
use Modules\Hr\Enums\EmployeeDocumentType;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Enums\SkillProficiencyLevel;
use Modules\Hr\Http\Requests\Concerns\MapsEmployeeData;

final class EmployeeRelationRequest extends TenantScopedRequest
{
    use MapsEmployeeData;
    public function rules(): array
    {
        $base = ['tenant_id' => ['required', 'integer', 'min:1'], 'organization_unit_id' => ['nullable', 'integer', 'min:1']];
        return $base + match ((string) $this->route()->getName()) {
            'api.v1.hr.contacts.store', 'api.v1.hr.contacts.update' => ['contact_name' => ['required', 'string', 'max:255'], 'relationship' => ['nullable', 'string', 'max:100'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string', 'max:50'], 'mobile' => ['nullable', 'string', 'max:50'], 'is_emergency_contact' => ['nullable', 'boolean'], 'is_primary' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'], 'notes' => ['nullable', 'string']],
            'api.v1.hr.addresses.store', 'api.v1.hr.addresses.update' => ['address_type' => ['required', Rule::enum(EmployeeAddressType::class)], 'address_line_1' => ['required', 'string', 'max:255'], 'address_line_2' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string'], 'state' => ['nullable', 'string'], 'postal_code' => ['nullable', 'string'], 'country' => ['nullable', 'string'], 'is_primary' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean']],
            'api.v1.hr.documents.store', 'api.v1.hr.documents.update' => ['document_type' => ['required', Rule::enum(EmployeeDocumentType::class)], 'document_number' => ['nullable', 'string'], 'issued_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date'], 'status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)], 'notes' => ['nullable', 'string']],
            'api.v1.hr.skills.store', 'api.v1.hr.skills.update' => ['skill_id' => ['required', 'integer', 'min:1'], 'proficiency_level' => ['nullable', Rule::enum(SkillProficiencyLevel::class)], 'years_of_experience' => ['nullable', 'decimal:0,6', 'gte:0'], 'is_primary' => ['nullable', 'boolean']],
            'api.v1.hr.certifications.store', 'api.v1.hr.certifications.update' => ['certification_id' => ['required', 'integer', 'min:1'], 'certificate_number' => ['nullable', 'string'], 'issued_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date'], 'status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)]],
            'api.v1.hr.licenses.store', 'api.v1.hr.licenses.update' => ['license_id' => ['required', 'integer', 'min:1'], 'license_number' => ['nullable', 'string'], 'issued_date' => ['nullable', 'date'], 'expiry_date' => ['nullable', 'date'], 'status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)]],
            'api.v1.hr.rates.store', 'api.v1.hr.rates.update' => ['rate_type' => ['required', Rule::enum(EmployeeRateType::class)], 'amount' => ['required', 'decimal:0,6', 'gte:0'], 'currency_id' => ['nullable', 'integer', 'min:1'], 'effective_from' => ['nullable', 'date'], 'effective_to' => ['nullable', 'date'], 'is_active' => ['nullable', 'boolean']],
            'api.v1.hr.availability.store', 'api.v1.hr.availability.update' => ['availability_status' => ['required', Rule::enum(EmployeeAvailabilityStatus::class)], 'availability_date' => ['nullable', 'date'], 'source_type' => ['nullable', 'string'], 'source_id' => ['nullable', 'integer', 'min:1'], 'reason' => ['nullable', 'string'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date']],
            default => [],
        };
    }
}
