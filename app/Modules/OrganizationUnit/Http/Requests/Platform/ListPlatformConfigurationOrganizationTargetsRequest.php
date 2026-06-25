<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

final class ListPlatformConfigurationOrganizationTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    public function perPage(): int
    {
        return min(max((int) $this->input('per_page', 20), 1), 50);
    }
}
