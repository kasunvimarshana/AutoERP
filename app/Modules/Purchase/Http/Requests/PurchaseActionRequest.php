<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class PurchaseActionRequest extends PurchaseRequest
{
    private const REVERSAL_REASON_MAX_LENGTH = 1000;

    public function rules(): array
    {
        $reversal = $this->routeIs('api.v1.purchase.goods-receipts.reverse');

        return array_merge($this->scopeRules(), [
            'expected_version' => ['required', 'integer', 'min:1'],
            'reversal_date' => $reversal
                ? ['required', 'date_format:Y-m-d']
                : ['prohibited'],
            'reason' => $reversal
                ? ['required', 'string', 'max:'.self::REVERSAL_REASON_MAX_LENGTH]
                : ['prohibited'],
        ]);
    }

    public function expectedVersion(): int
    {
        return (int) $this->input('expected_version');
    }

    public function reversalDate(): string
    {
        return (string) $this->input('reversal_date');
    }

    public function reversalReason(): string
    {
        return trim((string) $this->input('reason'));
    }
}
