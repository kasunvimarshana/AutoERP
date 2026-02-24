<?php

namespace Modules\SubscriptionBilling\Domain\Contracts;

interface SubscriptionPlanRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByCode(string $tenantId, string $code): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
}
