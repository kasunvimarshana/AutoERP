<?php
namespace Modules\UoM\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class UomConversion extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $fromUomId,
        public readonly int $toUomId,
        public readonly float $factor,
        public readonly ?int $productId = null,
    ) {
        parent::__construct($id);
    }

    public function convert(float $qty): float
    {
        return $qty * $this->factor;
    }
}
