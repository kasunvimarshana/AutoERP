<?php
declare(strict_types=1);
namespace Modules\Procurement\Domain\Contracts;
use Modules\Procurement\Domain\Entities\PurchaseOrder;
interface PurchaseRepositoryInterface {
    public function findById(int $id, int $tenantId): ?PurchaseOrder;
    public function findByPoNumber(string $poNumber, int $tenantId): ?PurchaseOrder;
    public function save(PurchaseOrder $order): PurchaseOrder;
    public function delete(int $id, int $tenantId): void;
    public function generatePoNumber(int $tenantId): string;
}
