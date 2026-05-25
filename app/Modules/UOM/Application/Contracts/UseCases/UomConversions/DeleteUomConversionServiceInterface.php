<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UomConversions;

use Modules\Core\Application\Results\Result;

interface DeleteUomConversionServiceInterface
{
    public function execute(int|string $id): Result;
}