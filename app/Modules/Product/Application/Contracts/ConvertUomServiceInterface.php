<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

interface ConvertUomServiceInterface
{
    public function convert(int $fromUomId, int $toUomId, string $quantity): string;
}
