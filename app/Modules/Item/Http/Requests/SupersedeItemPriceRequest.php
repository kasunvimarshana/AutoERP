<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Item\DTOs\SupersedeItemPriceData;

final class SupersedeItemPriceRequest extends ItemPriceRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'expected_version' => ['required', 'integer', 'min:1'],
            'correction_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function toData(): SupersedeItemPriceData
    {
        return new SupersedeItemPriceData(
            price: $this->toPriceData(),
            expectedVersion: (int) $this->input('expected_version'),
            correctionReason: trim((string) $this->input('correction_reason')),
        );
    }
}
