<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Refund;

interface RefundRepositoryInterface
{
    public function findById(int $id): ?Refund;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByPayment(int $paymentId): array;
    public function create(array $data): Refund;
    public function update(int $id, array $data): ?Refund;
    public function delete(int $id): bool;
}
