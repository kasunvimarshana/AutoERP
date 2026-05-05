<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
            'issuing_authority' => 'nullable|string|max:255',
            'file_url' => 'nullable|url',
        ];
    }
}
