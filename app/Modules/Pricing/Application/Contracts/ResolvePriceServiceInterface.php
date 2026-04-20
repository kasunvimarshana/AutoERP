<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Contracts;

interface ResolvePriceServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function execute(array $data = []): array;
}
