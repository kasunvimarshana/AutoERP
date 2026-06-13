<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class SalesActionRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'reason' => ['nullable', 'string'],
        ]);
    }
}
