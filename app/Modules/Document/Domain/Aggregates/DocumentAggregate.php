<?php

namespace Modules\Document\Domain\Aggregates;

use Modules\Document\Domain\Entities\Document;
use Modules\Document\Domain\Entities\DocumentItem;
use Modules\Document\Domain\Exceptions\DocumentValidationException;

class DocumentAggregate
{
    /**
     * @param  array<int, DocumentItem>  $items
     */
    public function __construct(
        public Document $document,
        public array $items = [],
    ) {}

    public function validate(): void
    {
        if ($this->items === []) {
            throw new DocumentValidationException('A document must contain at least one item.');
        }
    }

    public function calculateTotals(): void
    {
        $subtotal = '0.0000';
        $discount = '0.0000';
        $tax = '0.0000';
        $grand = '0.0000';

        foreach ($this->items as $item) {
            $quantity = $this->asDecimal($item->data['quantity'] ?? 1);
            $unitPrice = $this->asDecimal($item->data['unit_price'] ?? $item->data['amount'] ?? 0);
            $discountAmount = $this->asDecimal($item->data['discount_amount'] ?? 0);
            $taxAmount = $this->asDecimal($item->data['tax_amount'] ?? 0);

            $lineSubtotal = bccomp($unitPrice, '0.0000', 4) === 1
                ? bcmul($quantity, $unitPrice, 4)
                : bcadd(bcsub($this->asDecimal($item->lineTotal), $taxAmount, 4), $discountAmount, 4);

            $subtotal = bcadd($subtotal, $lineSubtotal, 4);
            $discount = bcadd($discount, $discountAmount, 4);
            $tax = bcadd($tax, $taxAmount, 4);
            $grand = bcadd($grand, $this->asDecimal($item->lineTotal), 4);
        }

        $this->document->subtotal = $subtotal;
        $this->document->discountTotal = $discount;
        $this->document->taxTotal = $tax;
        $this->document->grandTotal = $grand;
    }

    private function asDecimal(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
