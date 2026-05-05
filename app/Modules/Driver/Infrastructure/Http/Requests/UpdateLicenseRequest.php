<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'expiry_date' => 'nullable|date',
            'issuing_country' => 'nullable|string|max:2',
        ];
    }
}
