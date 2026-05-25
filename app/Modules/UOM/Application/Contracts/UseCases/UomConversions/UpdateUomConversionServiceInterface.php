<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UomConversions;

use Modules\Core\Application\Results\Result;

interface UpdateUomConversionServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}