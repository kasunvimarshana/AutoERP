<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\PayrollRun;

class PayrollRunApproved extends BaseEvent
{
    public function __construct(
        public readonly PayrollRun $payrollRun,
        int $tenantId,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'payrollRunId' => $this->payrollRun->getId(),
            'approvedBy' => $this->payrollRun->getApprovedBy(),
            'totalNet' => $this->payrollRun->getTotalNet(),
            'status' => $this->payrollRun->getStatus()->value,
        ]);
    }
}
