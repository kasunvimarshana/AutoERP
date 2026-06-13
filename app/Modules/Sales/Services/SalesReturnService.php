<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Sales\DTOs\CreateSalesReturnData;
use Modules\Sales\DTOs\SalesPostingResult;
use Modules\Sales\Enums\SalesReturnStatus;
use Modules\Sales\Models\SalesReturn;

/**
 * Stable return workflow API used by controllers and module integrations.
 */
final class SalesReturnService
{
    public function __construct(
        private readonly SalesReturnWriteService $writes,
        private readonly SalesReturnPostingService $posting,
        private readonly SalesReturnAdjustmentService $adjustments,
        private readonly SalesStatusService $statuses,
    ) {}

    public function create(CreateSalesReturnData $data): SalesReturn
    {
        return $this->load($this->writes->create($data));
    }

    public function approve(SalesReturn $return, ?int $userId = null): SalesReturn
    {
        $this->statuses->transition($return, SalesReturnStatus::Approved, $userId);
        $return->approved_by = $userId;
        $return->approved_at = now();
        $return->save();

        return $this->load($return);
    }

    public function post(SalesReturn $return, ?int $userId = null): SalesPostingResult
    {
        return $this->posting->post($return, $userId);
    }

    public function cancel(SalesReturn $return, ?int $userId = null): SalesReturn
    {
        return DB::transaction(function () use ($return, $userId): SalesReturn {
            $return = SalesReturn::query()
                ->lockForUpdate()
                ->findOrFail($return->getKey());
            if ($return->status === SalesReturnStatus::Posted) {
                throw new InvalidArgumentException(
                    'Posted sales returns cannot be cancelled.',
                );
            }

            $this->statuses->transition($return, SalesReturnStatus::Cancelled, $userId);
            $this->adjustments->release($return);

            return $this->load($return);
        });
    }

    private function load(SalesReturn $return): SalesReturn
    {
        return $return->refresh()->load([
            'customer',
            'warehouse',
            'warehouseLocation',
            'replacementSalesOrder',
            'creditNote',
            'lines.item',
            'lines.variant',
            'lines.uom',
            'adjustmentAllocations',
        ]);
    }
}
