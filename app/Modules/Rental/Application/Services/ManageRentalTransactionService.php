<?php declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\ManageRentalTransactionServiceInterface;
use Modules\Rental\Domain\Entities\RentalTransaction;
use Modules\Rental\Domain\RepositoryInterfaces\RentalTransactionRepositoryInterface;

class ManageRentalTransactionService implements ManageRentalTransactionServiceInterface
{
    public function __construct(
        private readonly RentalTransactionRepositoryInterface $transactions,
    ) {}

    public function checkOut(array $data): RentalTransaction
    {
        return DB::transaction(function () use ($data): RentalTransaction {
            $transaction = new RentalTransaction(
                id: Str::uuid()->toString(),
                tenantId: (string) $data['tenant_id'],
                agreementId: $data['agreement_id'],
                vehicleId: $data['vehicle_id'],
                odometerStart: (int) $data['odometer_start'],
                fuelLevelStart: (string) $data['fuel_level_start'],
                checkOutAt: new \DateTime($data['check_out_at'] ?? 'now'),
                checkedOutBy: $data['checked_out_by'] ?? null,
                status: 'open',
            );

            $this->transactions->create($transaction);
            return $transaction;
        });
    }

    public function checkIn(int $tenantId, string $transactionId, array $data): RentalTransaction
    {
        return DB::transaction(function () use ($tenantId, $transactionId, $data): RentalTransaction {
            $transaction = $this->transactions->findById($transactionId);
            if (!$transaction || $transaction->getTenantId() !== (string) $tenantId) {
                throw new \Exception('Rental transaction not found');
            }

            $transaction->checkIn(
                (int) $data['odometer_end'],
                (string) $data['fuel_level_end'],
                $data['checked_in_by'] ?? null
            );

            $this->transactions->update($transaction);
            return $this->transactions->findById($transactionId);
        });
    }

    public function getOpen(int $tenantId): array
    {
        return $this->transactions->getOpen((string) $tenantId);
    }
}
