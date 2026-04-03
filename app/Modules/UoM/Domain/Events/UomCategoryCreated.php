<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;
use Modules\UoM\Domain\Entities\UomCategory;

class UomCategoryCreated extends BaseEvent
{
    public function __construct(public readonly UomCategory $category)
    {
        parent::__construct($category->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'   => $this->category->getId(),
            'name' => $this->category->getName(),
            'code' => $this->category->getCode(),
        ]);
    }
}
