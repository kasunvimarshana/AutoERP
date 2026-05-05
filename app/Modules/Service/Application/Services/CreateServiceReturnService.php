<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServiceReturnServiceInterface;
use Modules\Service\Domain\Entities\ServiceReturn;
use Modules\Service\Domain\RepositoryInterfaces\ServiceReturnRepositoryInterface;

class CreateServiceReturnService extends BaseService implements CreateServiceReturnServiceInterface
{
    public function __construct(private readonly ServiceReturnRepositoryInterface $returnRepository) {}

    protected function handle(array $data): ServiceReturn
    {
        $returnNumber = $data['return_number'] ?? strtoupper('RET-' . uniqid());

        $return = new ServiceReturn(
            tenantId: (int) $data['tenant_id'],
            serviceWorkOrderId: (int) $data['service_work_order_id'],
            returnNumber: $returnNumber,
            returnType: $data['return_type'] ?? 'inventory_return',
            status: 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            reasonCode: $data['reason_code'] ?? null,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            totalAmount: isset($data['total_amount']) ? (float) $data['total_amount'] : 0.0,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->returnRepository->save($return);
    }
}
