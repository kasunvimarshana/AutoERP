<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:internal,external',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ];
    }
}
