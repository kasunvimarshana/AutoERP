<?php
namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class GS1LabelData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $barcodeId,
        public readonly string $labelFormat,
        public readonly ?int $batchId = null,
        public readonly ?string $serialNumber = null,
    ) {}
}
