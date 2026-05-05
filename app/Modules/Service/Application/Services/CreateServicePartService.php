<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServicePartServiceInterface;
use Modules\Service\Domain\Entities\ServicePart;
use Modules\Service\Domain\RepositoryInterfaces\ServicePartRepositoryInterface;

class CreateServicePartService extends BaseService implements CreateServicePartServiceInterface
{
    public function __construct(private readonly ServicePartRepositoryInterface $partRepository) {}

    protected function handle(array $data): ServicePart
    {
        $quantity = isset($data['quantity']) ? (float) $data['quantity'] : 0.0;
        $unitCost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : 0.0;
        $unitPrice = isset($data['unit_price']) ? (float) $data['unit_price'] : 0.0;
        $lineAmount = $quantity * $unitPrice;

        $part = new ServicePart(
            tenantId: (int) $data['tenant_id'],
            serviceWorkOrderId: (int) $data['service_work_order_id'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            serviceTaskId: isset($data['service_task_id']) ? (int) $data['service_task_id'] : null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            partSource: $data['part_source'] ?? 'inventory',
            description: $data['description'] ?? null,
            quantity: $quantity,
            uomId: isset($data['uom_id']) ? (int) $data['uom_id'] : null,
            unitCost: $unitCost,
            unitPrice: $unitPrice,
            lineAmount: $lineAmount,
            isReturned: false,
            isWarrantyCovered: (bool) ($data['is_warranty_covered'] ?? false),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->partRepository->save($part);
    }
}
