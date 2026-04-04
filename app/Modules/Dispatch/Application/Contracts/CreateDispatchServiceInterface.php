<?php

namespace Modules\Dispatch\Application\Contracts;

use Modules\Dispatch\Application\DTOs\DispatchData;
use Modules\Dispatch\Domain\Entities\Dispatch;

interface CreateDispatchServiceInterface
{
    public function execute(DispatchData $data): Dispatch;
}
