<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class PurchaseActionRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return $this->scopeRules();
    }
}
