<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UomConversions;

use Modules\Core\Application\Results\Result;

interface CreateUomConversionServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}