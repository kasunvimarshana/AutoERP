<?php
namespace Modules\Inventory\Domain\Events;
use Modules\Shared\Domain\Events\DomainEvent;
class ProductCreated extends DomainEvent
{
    public function __construct(public readonly string $productId) {}
}
