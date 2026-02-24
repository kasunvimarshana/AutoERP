<?php

namespace Modules\POS\Domain\Contracts;

interface PosOrderPaymentRepositoryInterface
{
    public function create(array $data): object;

    /** @return object[] */
    public function findByOrderId(string $orderId): array;
}
