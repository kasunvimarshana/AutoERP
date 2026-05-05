<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Application\Contracts;

use Modules\ReturnRefund\Application\DTOs\ProcessReturnInput;
use Modules\ReturnRefund\Application\DTOs\ProcessReturnResult;

interface ProcessReturnAndRefundServiceInterface
{
    public function execute(ProcessReturnInput $input): ProcessReturnResult;
}
