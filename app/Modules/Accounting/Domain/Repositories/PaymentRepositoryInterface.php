<?php
namespace Modules\Accounting\Domain\Repositories;

use Modules\Accounting\Domain\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function findById(int $id): ?Payment;
    public function findByReference(int $tenantId, string $ref): ?Payment;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Payment;
    public function update(Payment $payment, array $data): Payment;
    public function delete(Payment $payment): bool;
}
