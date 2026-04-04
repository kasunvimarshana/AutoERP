<?php
namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class GS1BarcodeData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $gs1CompanyPrefix,
        public readonly string $itemReference,
        public readonly string $barcodeType,
        public readonly ?int $variantId = null,
    ) {}
}
