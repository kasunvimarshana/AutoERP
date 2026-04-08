<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\Events;

use Modules\Brand\Domain\Entities\Brand;
use Modules\Core\Domain\Events\BaseEvent;

class BrandCreated extends BaseEvent
{
    public function __construct(public readonly Brand $brand)
    {
        parent::__construct($brand->getTenantId());
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'id'     => $this->brand->getId(),
            'name'   => $this->brand->getName(),
            'slug'   => $this->brand->getSlug(),
            'status' => $this->brand->getStatus(),
        ]);
    }
}
