<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class PurchaseActionRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }
}
