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
        public array $items = []
    ) {
    }

    public function validate(): void
    {
        if ($this->items === []) {
            throw new DocumentValidationException('A document must contain at least one item.');
        }
    }

    public function calculateTotals(): void
    {
        $grandTotal = '0.0000';

        foreach ($this->items as $item) {
            $grandTotal = bcadd($grandTotal, $this->asDecimal($item->lineTotal), 4);
        }

        $this->document->subtotal = $grandTotal;
        $this->document->discountTotal = '0.0000';
        $this->document->taxTotal = '0.0000';
        $this->document->grandTotal = $grandTotal;
    }

    private function asDecimal(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
