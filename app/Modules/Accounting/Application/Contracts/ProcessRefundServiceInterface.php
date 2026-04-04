<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\RefundData;
use Modules\Accounting\Domain\Entities\Refund;

interface ProcessRefundServiceInterface
{
    public function execute(RefundData $data): Refund;
}
