<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\ReleaseRentalDepositServiceInterface;
use Modules\Rental\Domain\Entities\RentalDeposit;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;

class ReleaseRentalDepositService extends BaseService implements ReleaseRentalDepositServiceInterface
{
    public function __construct(private readonly RentalDepositRepositoryInterface $depositRepository) {}

    protected function handle(array $data): RentalDeposit
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->depositRepository->findById($tenantId, $id);
        if ($existing === null) {
            throw new \RuntimeException("Deposit #{$id} not found.");
        }

        if (in_array($existing->getStatus(), ['released', 'forfeited'], true)) {
            throw new \RuntimeException('Deposit is already released or forfeited.');
        }

        $releasedAmount = isset($data['released_amount']) ? (float) $data['released_amount'] : $existing->getHeldAmount();
        $forfeitedAmount = $existing->getHeldAmount() - $releasedAmount;
        $newStatus = abs($releasedAmount) < PHP_FLOAT_EPSILON
            ? 'forfeited'
            : (abs($forfeitedAmount) < PHP_FLOAT_EPSILON ? 'released' : 'partially_released');

        $released = new RentalDeposit(
            tenantId: $existing->getTenantId(),
            rentalBookingId: $existing->getRentalBookingId(),
            currencyId: $existing->getCurrencyId(),
            heldAmount: $existing->getHeldAmount(),
            status: $newStatus,
            orgUnitId: $existing->getOrgUnitId(),
            releasedAmount: $releasedAmount,
            forfeitedAmount: $forfeitedAmount > 0.0 ? $forfeitedAmount : 0.0,
            heldAt: $existing->getHeldAt(),
            releasedAt: $data['released_at'] ?? now()->toISOString(),
            paymentId: isset($data['payment_id']) ? (int) $data['payment_id'] : $existing->getPaymentId(),
            journalEntryId: isset($data['journal_entry_id']) ? (int) $data['journal_entry_id'] : $existing->getJournalEntryId(),
            metadata: $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->depositRepository->save($released);
    }
}
