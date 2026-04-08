<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

class FindGoodsReceiptService extends BaseService implements FindGoodsReceiptServiceInterface
{
    public function __construct(private readonly GoodsReceiptRepositoryInterface $receiptRepository)
    {
        parent::__construct($receiptRepository);
    }

    public function findByPurchaseOrder(int $tenantId, int $poId): Collection
    {
        return $this->receiptRepository->findByPurchaseOrder($tenantId, $poId);
    }

    public function findBySupplier(int $tenantId, int $supplierId): Collection
    {
        return $this->receiptRepository->findBySupplier($tenantId, $supplierId);
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->receiptRepository->findByStatus($tenantId, $status);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
