<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class UpdatePurchaseOrderRequest extends StorePurchaseOrderRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_version' => ['required', 'integer', 'min:1'],
        ]);
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }
}
