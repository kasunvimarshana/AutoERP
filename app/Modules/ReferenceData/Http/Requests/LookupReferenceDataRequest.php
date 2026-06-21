<?php

declare(strict_types=1);

namespace Modules\ReferenceData\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class LookupReferenceDataRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function perPage(): int
    {
        $value = $this->input('per_page', 20);
        $perPage = is_numeric($value) ? (int) $value : 20;

        return min(max($perPage, 1), 1000);
    }
}
