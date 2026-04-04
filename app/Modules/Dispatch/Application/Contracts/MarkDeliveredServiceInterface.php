<?php

namespace Modules\Dispatch\Application\Contracts;

use Modules\Dispatch\Domain\Entities\Dispatch;

interface MarkDeliveredServiceInterface
{
    public function execute(Dispatch $dispatch): Dispatch;
}
