<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDriverLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_number' => ['required', 'string', 'max:100'],
            'license_class'  => ['required', 'string', 'max:50'],
            'issued_country' => ['nullable', 'string', 'size:3'],
            'issue_date'     => ['nullable', 'date'],
            'expiry_date'    => ['nullable', 'date'],
            'file_path'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
