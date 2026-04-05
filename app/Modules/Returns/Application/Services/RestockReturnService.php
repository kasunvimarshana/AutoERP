<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\RestockReturnServiceInterface;
use Modules\Returns\Domain\Entities\ReturnLine;
use Modules\Returns\Domain\Entities\ReturnRequest;
use Modules\Returns\Domain\Events\ReturnRestocked;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRequestRepositoryInterface;

class RestockReturnService implements RestockReturnServiceInterface
{
    public function __construct(
        private readonly ReturnRequestRepositoryInterface $repo,
    ) {}

    public function execute(
        int $returnRequestId,
        int $restockedBy,
        int $warehouseId,
        float $restockingFee = 0.0
    ): ReturnRequest {
        return DB::transaction(function () use ($returnRequestId, $restockedBy, $warehouseId, $restockingFee): ReturnRequest {
            $ret = $this->repo->findById($returnRequestId);
            if (!$ret) {
                throw new NotFoundException('ReturnRequest', $returnRequestId);
            }

            // Transition to restocking
            $ret->startRestocking();
            $this->repo->update($returnRequestId, ['status' => ReturnRequest::STATUS_RESTOCKING]);

            // Process each line: quality-approve good/damaged, reject unsellable
            foreach ($ret->getLines() as $line) {
                $condition     = $line['condition'] ?? ReturnLine::CONDITION_GOOD;
                $qualityStatus = $line['quality_status'] ?? ReturnLine::QUALITY_PENDING;

                if ($qualityStatus === ReturnLine::QUALITY_PENDING) {
                    $newQuality = ($condition !== ReturnLine::CONDITION_UNSELLABLE)
                        ? ReturnLine::QUALITY_APPROVED
                        : ReturnLine::QUALITY_REJECTED;

                    $this->repo->updateLine((int)$line['id'], [
                        'quality_status'              => $newQuality,
                        'restocked_to_warehouse_id'   => $newQuality === ReturnLine::QUALITY_APPROVED ? $warehouseId : null,
                        'restocked_quantity'          => $newQuality === ReturnLine::QUALITY_APPROVED ? (float)$line['quantity_returned'] : null,
                    ]);
                }
            }

            // Complete restocking (credit memo creation is handled by the caller/event listener)
            $ret->completeRestock($restockedBy);
            $this->repo->update($returnRequestId, [
                'status'          => ReturnRequest::STATUS_RESTOCKED,
                'restocking_fee'  => $restockingFee,
                'restocked_by'    => $restockedBy,
                'restocked_at'    => now(),
            ]);

            event(new ReturnRestocked($ret->getTenantId(), $returnRequestId, $restockedBy));

            return $this->repo->findById($returnRequestId);
        });
    }
}
