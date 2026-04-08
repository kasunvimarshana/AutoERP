<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Events;

use Modules\Category\Domain\Entities\Category;
use Modules\Core\Domain\Events\BaseEvent;

class CategoryUpdated extends BaseEvent
{
    public function __construct(public readonly Category $category)
    {
        parent::__construct($category->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'        => $this->category->getId(),
            'name'      => $this->category->getName(),
            'slug'      => $this->category->getSlug(),
            'parent_id' => $this->category->getParentId(),
            'status'    => $this->category->getStatus(),
        ]);
    }
}
