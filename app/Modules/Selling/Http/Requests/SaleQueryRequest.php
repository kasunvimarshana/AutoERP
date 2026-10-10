<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class SaleQueryRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
