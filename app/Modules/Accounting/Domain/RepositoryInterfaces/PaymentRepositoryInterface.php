<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByPayable(string $payableType, int $payableId): array;
    public function create(array $data): Payment;
    public function update(int $id, array $data): ?Payment;
    public function delete(int $id): bool;
}
