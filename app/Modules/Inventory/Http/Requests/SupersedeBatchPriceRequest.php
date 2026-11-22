<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Http\Requests;

use Modules\Inventory\DTOs\SupersedeBatchPriceData;

final class SupersedeBatchPriceRequest extends BatchPriceRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'expected_version' => ['required', 'integer', 'min:1'],
            'correction_reason' => ['required', 'string', 'max:1000'],
        ]);
    }

    public function toData(): SupersedeBatchPriceData
    {
        return new SupersedeBatchPriceData(
            price: $this->toPriceData(),
            expectedVersion: (int) $this->input('expected_version'),
            correctionReason: (string) $this->input('correction_reason'),
        );
    }
}
