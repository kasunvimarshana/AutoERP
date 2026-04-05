<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\ReturnRequest;

interface RestockReturnServiceInterface
{
    /**
     * Execute the restocking workflow for an approved return:
     *  1. Validate the return is in 'approved' state.
     *  2. Transition it to 'restocking'.
     *  3. For each line that passes quality check, add inventory layer back.
     *  4. Apply restocking fee (if any) and optionally generate a credit memo.
     *  5. Transition to 'restocked'.
     *
     * @param int $returnRequestId
     * @param int $restockedBy    User performing the restock
     * @param int $warehouseId    Target warehouse for restocked goods
     * @param float $restockingFee Optional restocking fee to deduct from credit memo
     * @return ReturnRequest
     */
    public function execute(int $returnRequestId, int $restockedBy, int $warehouseId, float $restockingFee = 0.0): ReturnRequest;
}
