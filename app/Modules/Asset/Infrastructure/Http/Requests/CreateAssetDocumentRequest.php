<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssetDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'asset_id' => 'required|string|uuid',
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'file_url' => 'nullable|url',
        ];
    }
}
