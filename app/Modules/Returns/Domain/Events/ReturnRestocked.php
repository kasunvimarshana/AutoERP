<?php
declare(strict_types=1);
namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnRestocked extends BaseEvent
{
    public int $returnRequestId;
    public int $restockedBy;

    public function __construct(int $tenantId, int $returnRequestId, int $restockedBy)
    {
        parent::__construct($tenantId);
        $this->returnRequestId = $returnRequestId;
        $this->restockedBy     = $restockedBy;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'returnRequestId' => $this->returnRequestId,
            'restockedBy'     => $this->restockedBy,
        ]);
    }
}
