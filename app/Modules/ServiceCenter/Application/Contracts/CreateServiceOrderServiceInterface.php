<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Contracts;

use Modules\ServiceCenter\Application\DTOs\CreateServiceOrderDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;

interface CreateServiceOrderServiceInterface
{
    public function execute(CreateServiceOrderDTO $dto): ServiceOrder;
}
