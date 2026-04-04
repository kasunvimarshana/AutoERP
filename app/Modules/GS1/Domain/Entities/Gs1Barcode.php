<?php
namespace Modules\GS1\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class GS1Barcode extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $gs1CompanyPrefix,
        public readonly string $itemReference,
        public readonly string $checkDigit,
        public readonly string $gtin,
        public readonly string $barcodeType,
        public readonly ?int $variantId = null,
        public readonly bool $isActive = true,
    ) {
        parent::__construct($id);
    }
}
