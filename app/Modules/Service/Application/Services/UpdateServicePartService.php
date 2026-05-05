<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServicePartServiceInterface;
use Modules\Service\Domain\Entities\ServicePart;
use Modules\Service\Domain\RepositoryInterfaces\ServicePartRepositoryInterface;
use RuntimeException;

class UpdateServicePartService extends BaseService implements UpdateServicePartServiceInterface
{
    public function __construct(private readonly ServicePartRepositoryInterface $partRepository) {}

    protected function handle(array $data): ServicePart
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->partRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new RuntimeException("Service part {$id} not found.");
        }

        $quantity = isset($data['quantity']) ? (float) $data['quantity'] : $existing->getQuantity();
        $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : $existing->getUnitPrice();

        $updated = new ServicePart(
            tenantId: $existing->getTenantId(),
            serviceWorkOrderId: $existing->getServiceWorkOrderId(),
            orgUnitId: $existing->getOrgUnitId(),
            serviceTaskId: $existing->getServiceTaskId(),
            productId: isset($data['product_id']) ? (int) $data['product_id'] : $existing->getProductId(),
            partSource: $data['part_source'] ?? $existing->getPartSource(),
            description: $data['description'] ?? $existing->getDescription(),
            quantity: $quantity,
            uomId: isset($data['uom_id']) ? (int) $data['uom_id'] : $existing->getUomId(),
            unitCost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : $existing->getUnitCost(),
            unitPrice: $unitPrice,
            lineAmount: $quantity * $unitPrice,
            isReturned: (bool) ($data['is_returned'] ?? $existing->isReturned()),
            isWarrantyCovered: (bool) ($data['is_warranty_covered'] ?? $existing->isWarrantyCovered()),
            stockReferenceType: $data['stock_reference_type'] ?? $existing->getStockReferenceType(),
            stockReferenceId: isset($data['stock_reference_id']) ? (int) $data['stock_reference_id'] : $existing->getStockReferenceId(),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->partRepository->save($updated);
    }
}
