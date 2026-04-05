<?php
declare(strict_types=1);
namespace Modules\Returns\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class ReturnCompleted extends BaseEvent
{
    public int $returnRequestId;

    public function __construct(int $tenantId, int $returnRequestId)
    {
        parent::__construct($tenantId);
        $this->returnRequestId = $returnRequestId;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'returnRequestId' => $this->returnRequestId,
        ]);
    }
}
