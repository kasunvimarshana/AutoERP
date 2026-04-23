<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'employee_id' => 'required|integer',
            'document_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_path' => 'required|string',
            'mime_type' => 'nullable|string|max:100',
            'file_size' => 'nullable|integer|min:0',
            'issued_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'metadata' => 'nullable|array',
        ];
    }
}
