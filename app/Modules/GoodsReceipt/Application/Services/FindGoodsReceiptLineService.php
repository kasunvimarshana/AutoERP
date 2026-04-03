<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;

class FindGoodsReceiptLineService extends BaseService implements FindGoodsReceiptLineServiceInterface
{
    public function __construct(private readonly GoodsReceiptLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findByGoodsReceipt(int $goodsReceiptId): Collection
    {
        return $this->lineRepository->findByGoodsReceipt($goodsReceiptId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
