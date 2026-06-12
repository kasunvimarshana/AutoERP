<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Customer\DTOs\CustomerDocumentData;
use Modules\Customer\Enums\CustomerDocumentStatus;
use Modules\Customer\Enums\CustomerDocumentType;

abstract class CustomerDocumentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type' => ['required', Rule::enum(CustomerDocumentType::class)],
            'document_number' => ['nullable', 'string', 'max:150'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::enum(CustomerDocumentStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): CustomerDocumentData
    {
        return new CustomerDocumentData(
            documentType: CustomerDocumentType::from((string) $this->input('document_type')),
            documentNumber: $this->nullableString('document_number'),
            issuedDate: $this->nullableString('issued_date'),
            expiryDate: $this->nullableString('expiry_date'),
            filePath: $this->nullableString('file_path'),
            status: CustomerDocumentStatus::from((string) $this->input('status', CustomerDocumentStatus::Pending->value)),
            notes: $this->nullableString('notes'),
        );
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
