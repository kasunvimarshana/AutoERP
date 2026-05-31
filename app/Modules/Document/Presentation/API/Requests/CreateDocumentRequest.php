<?php

namespace Modules\Document\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'organization_unit_id' => ['nullable', 'integer'],
            'owner_id' => ['nullable', 'integer'],
            'party_id' => ['nullable', 'integer'],
            'document_definition_id' => ['nullable', 'integer', 'exists:document_definitions,id'],
            'source_module' => ['nullable', 'string', 'max:120'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer'],
            'source_reference' => ['nullable', 'string', 'max:180'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'data' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', 'max:100'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.line_total' => ['required', 'numeric'],
            'items.*.data' => ['nullable', 'array'],
        ];
    }
}
