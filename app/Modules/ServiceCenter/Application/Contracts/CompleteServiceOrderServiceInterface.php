<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\Contracts;

use Modules\ServiceCenter\Application\DTOs\CompleteServiceOrderDTO;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;

interface CompleteServiceOrderServiceInterface
{
    public function execute(CompleteServiceOrderDTO $dto): ServiceOrder;
}
