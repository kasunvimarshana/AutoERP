<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OrganizationUnitVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
