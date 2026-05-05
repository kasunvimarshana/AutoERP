<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\CreateRentalRateCardServiceInterface;
use Modules\Rental\Domain\Entities\RentalRateCard;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRateCardRepositoryInterface;

class CreateRentalRateCardService implements CreateRentalRateCardServiceInterface
{
    public function __construct(
        private readonly RentalRateCardRepositoryInterface $rateCardRepository,
    ) {}

    public function execute(array $data): RentalRateCard
    {
        $rateCard = new RentalRateCard(
            tenantId: (int) $data['tenant_id'],
            code: (string) $data['code'],
            name: (string) $data['name'],
            billingUom: (string) $data['billing_uom'],
            rate: (string) $data['rate'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            assetId: isset($data['asset_id']) ? (int) $data['asset_id'] : null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            depositPercentage: $data['deposit_percentage'] ?? null,
            priority: isset($data['priority']) ? (int) $data['priority'] : 100,
            validFrom: isset($data['valid_from']) ? new \DateTimeImmutable($data['valid_from']) : null,
            validTo: isset($data['valid_to']) ? new \DateTimeImmutable($data['valid_to']) : null,
            notes: $data['notes'] ?? null,
        );

        return DB::transaction(fn (): RentalRateCard => $this->rateCardRepository->save($rateCard));
    }
}
