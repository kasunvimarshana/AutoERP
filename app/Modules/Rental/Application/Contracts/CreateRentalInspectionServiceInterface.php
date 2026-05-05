<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalInspection;

interface CreateRentalInspectionServiceInterface
{
    public function execute(array $data): RentalInspection;
}
