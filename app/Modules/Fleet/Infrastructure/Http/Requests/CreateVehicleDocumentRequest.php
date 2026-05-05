<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateVehicleDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type'     => ['required', 'in:insurance,registration,road_worthy,permit,service_record,other'],
            'document_number'   => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:150'],
            'issue_date'        => ['nullable', 'date'],
            'expiry_date'       => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file_path'         => ['nullable', 'string', 'max:500'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}
