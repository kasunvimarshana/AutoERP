<?php

namespace Modules\ECommerce\Domain\Contracts;

interface ECommerceOrderLineRepositoryInterface
{
    public function findByOrder(string $orderId): iterable;
    public function create(array $data): object;
    public function deleteByOrder(string $orderId): void;
}
