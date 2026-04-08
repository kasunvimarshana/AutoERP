<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptLineDeleted;
use Modules\GoodsReceipt\Domain\Exceptions\GoodsReceiptLineNotFoundException;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;

class DeleteGoodsReceiptLineService extends BaseService implements DeleteGoodsReceiptLineServiceInterface
{
    public function __construct(private readonly GoodsReceiptLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new GoodsReceiptLineNotFoundException($id);
        }

        $this->addEvent(new GoodsReceiptLineDeleted($line->getId()));

        return $this->lineRepository->delete($id);
    }
}
