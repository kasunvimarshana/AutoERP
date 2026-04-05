<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Application\Services;
use Modules\PurchaseOrder\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
class CreatePurchaseOrderService implements CreatePurchaseOrderServiceInterface {
    public function __construct(private readonly PurchaseOrderRepositoryInterface $repo) {}
    public function execute(array $data, array $lines): PurchaseOrder {
        $data['status'] = 'draft';
        return $this->repo->create($data, $lines);
    }
}
