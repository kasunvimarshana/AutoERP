<?php

declare(strict_types=1);

namespace Modules\Selling\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class SaleQueryRequest extends TenantScopedRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
