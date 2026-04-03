<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\PurchaseOrder\Application\Contracts\DeletePurchaseOrderLineServiceInterface;
use Modules\PurchaseOrder\Domain\Events\PurchaseOrderLineDeleted;
use Modules\PurchaseOrder\Domain\Exceptions\PurchaseOrderLineNotFoundException;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;

class DeletePurchaseOrderLineService extends BaseService implements DeletePurchaseOrderLineServiceInterface
{
    public function __construct(private readonly PurchaseOrderLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $line = $this->lineRepository->find($id);

        if (! $line) {
            throw new PurchaseOrderLineNotFoundException($id);
        }

        $this->addEvent(new PurchaseOrderLineDeleted($id));

        return $this->lineRepository->delete($id);
    }
}
