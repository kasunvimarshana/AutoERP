<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\Enums\EmployeeAddressType;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeDocumentStatus;
use Modules\Hr\Enums\EmployeeDocumentType;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Enums\SkillProficiencyLevel;
use Modules\Hr\Http\Requests\Concerns\MapsEmployeeData;

final class StoreEmployeeWithRelationsRequest extends TenantScopedRequest
{
    use MapsEmployeeData;
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'], 'organization_unit_id' => ['nullable', 'integer', 'min:1'], 'employee' => ['required', 'array'],
            ...StoreEmployeeRequest::employeeRules('employee.'),
            'contacts' => ['nullable', 'array'], 'contacts.*.contact_name' => ['required', 'string', 'max:255'], 'contacts.*.relationship' => ['nullable', 'string', 'max:100'], 'contacts.*.email' => ['nullable', 'email'], 'contacts.*.phone' => ['nullable', 'string', 'max:50'], 'contacts.*.mobile' => ['nullable', 'string', 'max:50'], 'contacts.*.is_emergency_contact' => ['nullable', 'boolean'], 'contacts.*.is_primary' => ['nullable', 'boolean'], 'contacts.*.is_active' => ['nullable', 'boolean'], 'contacts.*.notes' => ['nullable', 'string'],
            'addresses' => ['nullable', 'array'], 'addresses.*.address_type' => ['required', Rule::enum(EmployeeAddressType::class)], 'addresses.*.address_line_1' => ['required', 'string', 'max:255'], 'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'], 'addresses.*.city' => ['nullable', 'string', 'max:120'], 'addresses.*.state' => ['nullable', 'string', 'max:120'], 'addresses.*.postal_code' => ['nullable', 'string', 'max:40'], 'addresses.*.country' => ['nullable', 'string', 'max:120'], 'addresses.*.is_primary' => ['nullable', 'boolean'], 'addresses.*.is_active' => ['nullable', 'boolean'],
            'documents' => ['nullable', 'array'], 'documents.*.document_type' => ['required', Rule::enum(EmployeeDocumentType::class)], 'documents.*.document_number' => ['nullable', 'string', 'max:150'], 'documents.*.issued_date' => ['nullable', 'date'], 'documents.*.expiry_date' => ['nullable', 'date'], 'documents.*.status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)], 'documents.*.notes' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'], 'skills.*.skill_id' => ['required', 'integer', 'min:1'], 'skills.*.proficiency_level' => ['nullable', Rule::enum(SkillProficiencyLevel::class)], 'skills.*.years_of_experience' => ['nullable', 'decimal:0,6', 'gte:0'], 'skills.*.is_primary' => ['nullable', 'boolean'],
            'certifications' => ['nullable', 'array'], 'certifications.*.certification_id' => ['required', 'integer', 'min:1'], 'certifications.*.certificate_number' => ['nullable', 'string', 'max:150'], 'certifications.*.issued_date' => ['nullable', 'date'], 'certifications.*.expiry_date' => ['nullable', 'date'], 'certifications.*.status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)],
            'licenses' => ['nullable', 'array'], 'licenses.*.license_id' => ['required', 'integer', 'min:1'], 'licenses.*.license_number' => ['nullable', 'string', 'max:150'], 'licenses.*.issued_date' => ['nullable', 'date'], 'licenses.*.expiry_date' => ['nullable', 'date'], 'licenses.*.status' => ['nullable', Rule::enum(EmployeeDocumentStatus::class)],
            'rates' => ['nullable', 'array'], 'rates.*.rate_type' => ['required', Rule::enum(EmployeeRateType::class)], 'rates.*.amount' => ['required', 'decimal:0,6', 'gte:0'], 'rates.*.currency_id' => ['nullable', 'integer', 'min:1'], 'rates.*.effective_from' => ['nullable', 'date'], 'rates.*.effective_to' => ['nullable', 'date'], 'rates.*.is_active' => ['nullable', 'boolean'],
            'availability' => ['nullable', 'array'], 'availability.availability_status' => ['required_with:availability', Rule::enum(EmployeeAvailabilityStatus::class)], 'availability.availability_date' => ['nullable', 'date'], 'availability.source_type' => ['nullable', 'string', 'max:150'], 'availability.source_id' => ['nullable', 'integer', 'min:1'], 'availability.reason' => ['nullable', 'string'], 'availability.starts_at' => ['nullable', 'date'], 'availability.ends_at' => ['nullable', 'date'],
        ];
    }
    public function toData(): CreateEmployeeData { $data = $this->validated(); return $this->mapEmployeeData((array) $data['employee'], $data); }
}
