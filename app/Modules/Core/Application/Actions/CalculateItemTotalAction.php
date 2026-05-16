<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

final class CalculateItemTotalAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): float
    {
        $quantity = (float) ($payload['quantity'] ?? 1);
        $unitPrice = (float) ($payload['unit_price'] ?? $payload['amount'] ?? 0);
        $discount = (float) ($payload['discount_amount'] ?? 0);
        $tax = (float) ($payload['tax_amount'] ?? 0);

        return round(($quantity * $unitPrice) - $discount + $tax, 4);
    }
}
