<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\PayrollRecord;

class PayrollRecordProcessed extends BaseEvent
{
    public function __construct(public readonly PayrollRecord $payrollRecord)
    {
        parent::__construct($payrollRecord->getTenantId(), $payrollRecord->getId());
    }
}
