<?php

namespace Modules\Dispatch\Application\Contracts;

use Modules\Dispatch\Domain\Entities\Dispatch;

interface DispatchShipmentServiceInterface
{
    public function execute(Dispatch $dispatch, int $dispatchedBy): Dispatch;
}
