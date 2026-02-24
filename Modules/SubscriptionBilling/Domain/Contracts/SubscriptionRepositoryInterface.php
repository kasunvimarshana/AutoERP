<?php

namespace Modules\SubscriptionBilling\Domain\Contracts;

interface SubscriptionRepositoryInterface
{
    public function findById(string $id): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;

    /**
     * Chunk through active subscriptions whose renewal period has ended.
     * Used by the renewal command to avoid execution timeout on large datasets.
     */
    public function chunkDueForRenewal(string $tenantId = '', int $chunkSize = 100, callable $callback = null): void;
}
