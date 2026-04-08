<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\HR\Domain\Entities\Training;

class TrainingUpdated extends BaseEvent
{
    public function __construct(public readonly Training $training)
    {
        parent::__construct($training->getTenantId(), $training->getId());
    }
}
