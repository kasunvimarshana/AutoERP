<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class DeletePurchaseInvoiceService extends BaseService implements DeletePurchaseInvoiceServiceInterface
{
    public function __construct(private readonly PurchaseInvoiceRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseInvoiceNotFoundException($id);
        }

        return $this->repo->delete($id);
    }
}
