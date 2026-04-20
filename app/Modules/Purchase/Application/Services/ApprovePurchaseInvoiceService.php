<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Events\PurchaseInvoiceApproved;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class ApprovePurchaseInvoiceService extends BaseService implements ApprovePurchaseInvoiceServiceInterface
{
    public function __construct(private readonly PurchaseInvoiceRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseInvoiceNotFoundException($id);
        }

        if ($entity->getStatus() !== 'draft') {
            throw new \InvalidArgumentException('Purchase invoice cannot be approved in its current state.');
        }

        $entity->approve();
        $saved = $this->repo->save($entity);

        $this->addEvent(new PurchaseInvoiceApproved(
            tenantId: $saved->getTenantId(),
            purchaseInvoiceId: (int) $saved->getId(),
            supplierId: $saved->getSupplierId(),
        ));

        return $saved;
    }
}
