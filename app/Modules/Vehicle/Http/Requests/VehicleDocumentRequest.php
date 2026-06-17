<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleDocumentData;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;

abstract class VehicleDocumentRequest extends TenantScopedRequest
{
    private const MAX_FILE_KILOBYTES = 10240;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type' => ['required', Rule::enum(VehicleDocumentType::class)],
            'document_number' => ['nullable', 'string', 'max:150'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'file' => [
                'nullable',
                'file',
                'max:'.self::MAX_FILE_KILOBYTES,
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx',
            ],
            'file_path' => ['prohibited'],
            'status' => ['nullable', Rule::enum(VehicleDocumentStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): VehicleDocumentData
    {
        return new VehicleDocumentData(
            documentType: VehicleDocumentType::from((string) $this->input('document_type')),
            documentNumber: $this->filled('document_number') ? (string) $this->input('document_number') : null,
            issuedDate: $this->filled('issued_date') ? (string) $this->input('issued_date') : null,
            expiryDate: $this->filled('expiry_date') ? (string) $this->input('expiry_date') : null,
            file: $this->file('file'),
            status: VehicleDocumentStatus::from((string) $this->input('status', VehicleDocumentStatus::Pending->value)),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
