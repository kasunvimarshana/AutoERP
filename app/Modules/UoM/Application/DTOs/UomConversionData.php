<?php
namespace Modules\UoM\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class UomConversionData extends BaseDTO
{
    public function __construct(
        public readonly int $fromUomId,
        public readonly int $toUomId,
        public readonly float $factor,
        public readonly ?int $productId = null,
    ) {}
}
