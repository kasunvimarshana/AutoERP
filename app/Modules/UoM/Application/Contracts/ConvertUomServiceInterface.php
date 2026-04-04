<?php
namespace Modules\UoM\Application\Contracts;

interface ConvertUomServiceInterface
{
    public function execute(float $qty, int $fromUomId, int $toUomId, ?int $productId = null): float;
}
