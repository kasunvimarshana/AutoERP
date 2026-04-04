<?php
namespace Modules\Accounting\Domain\Repositories;

use Modules\Accounting\Domain\Entities\Refund;

interface RefundRepositoryInterface
{
    public function findById(int $id): ?Refund;
    public function findByPayment(int $paymentId): array;
    public function create(array $data): Refund;
    public function update(Refund $refund, array $data): Refund;
    public function delete(Refund $refund): bool;
}
