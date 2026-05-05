<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServiceWarrantyClaimServiceInterface;
use Modules\Service\Domain\Entities\ServiceWarrantyClaim;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWarrantyClaimRepositoryInterface;

class CreateServiceWarrantyClaimService extends BaseService implements CreateServiceWarrantyClaimServiceInterface
{
    public function __construct(private readonly ServiceWarrantyClaimRepositoryInterface $claimRepository) {}

    protected function handle(array $data): ServiceWarrantyClaim
    {
        $claim = new ServiceWarrantyClaim(
            tenantId: (int) $data['tenant_id'],
            serviceWorkOrderId: (int) $data['service_work_order_id'],
            warrantyProvider: (string) $data['warranty_provider'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            supplierId: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            claimNumber: $data['claim_number'] ?? null,
            status: 'draft',
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : null,
            claimAmount: isset($data['claim_amount']) ? (float) $data['claim_amount'] : 0.0,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->claimRepository->save($claim);
    }
}
