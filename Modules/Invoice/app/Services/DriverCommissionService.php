<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use App\Core\Exceptions\ServiceException;
use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Enums\CommissionStatus;
use Modules\Invoice\Models\DriverCommission;
use Modules\Invoice\Repositories\DriverCommissionRepository;
use Modules\Invoice\Repositories\InvoiceRepository;

/**
 * DriverCommission Service
 *
 * Contains business logic for DriverCommission operations
 */
class DriverCommissionService extends BaseService
{
    /**
     * DriverCommissionService constructor
     */
    public function __construct(
        DriverCommissionRepository $repository,
        private readonly InvoiceRepository $invoiceRepository
    ) {
        parent::__construct($repository);
    }

    /**
     * Calculate commission for invoice
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ServiceException
     */
    public function calculateCommission(array $data): DriverCommission
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $invoice = $this->invoiceRepository->find($data['invoice_id']);

            if (! $invoice) {
                throw new ServiceException('Invoice not found');
            }

            // Calculate commission amount if not provided
            if (! isset($data['commission_amount'])) {
                $commissionRate = $data['commission_rate'] ?? 0;
                $data['commission_amount'] = $invoice->total_amount * ($commissionRate / 100);
            }

            // Default status
            if (! isset($data['status'])) {
                $data['status'] = CommissionStatus::PENDING->value;
            }

            $commission = parent::create($data);

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $commission;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to calculate commission: '.$e->getMessage());
        }
    }

    /**
     * Mark commission as paid
     *
     * @throws ServiceException
     */
    public function markAsPaid(int $id, int $approvedBy): DriverCommission
    {
        // Check if we\'re already in a transaction (e.g., from orchestrator or test)
        $shouldManageTransaction = DB::transactionLevel() === 0;

        try {
            if ($shouldManageTransaction) {
                DB::beginTransaction();
            }

            $commission = $this->repository->find($id);

            if (! $commission) {
                throw new ServiceException('Commission not found');
            }

            if ($commission->status === CommissionStatus::PAID->value) {
                throw new ServiceException('Commission is already marked as paid');
            }

            $commission->status = CommissionStatus::PAID->value;
            $commission->paid_date = now();
            $commission->approved_by = $approvedBy;
            $commission->save();

            if ($shouldManageTransaction) {
                DB::commit();
            }

            return $commission;
        } catch (\Exception $e) {
            if ($shouldManageTransaction) {
                DB::rollBack();
            }
            throw new ServiceException('Failed to mark commission as paid: '.$e->getMessage());
        }
    }

    /**
     * Get commissions for driver
     */
    public function getForDriver(int $driverId): mixed
    {
        return $this->repository->getForDriver($driverId);
    }

    /**
     * Get pending commissions
     */
    public function getPending(): mixed
    {
        return $this->repository->getPending();
    }

    /**
     * Get commission with relations
     */
    public function getWithRelations(int $id): mixed
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Search commissions with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): mixed
    {
        return $this->repository->getWithFilters($filters);
    }
}
