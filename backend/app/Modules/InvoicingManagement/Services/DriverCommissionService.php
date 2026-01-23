<?php

namespace App\Modules\InvoicingManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InvoicingManagement\Events\CommissionCalculated;
use App\Modules\InvoicingManagement\Repositories\DriverCommissionRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DriverCommissionService extends BaseService
{
    public function __construct(DriverCommissionRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Calculate commission for a driver
     */
    public function calculateCommission(int $driverId, \DateTime $startDate, \DateTime $endDate, array $rules = []): Model
    {
        try {
            DB::beginTransaction();

            // Fetch driver's completed jobs/invoices in date range
            $invoices = app(\App\Modules\InvoicingManagement\Repositories\InvoiceRepository::class)
                ->getByDriverAndDateRange($driverId, $startDate, $endDate);

            $totalRevenue = 0;
            $commissionAmount = 0;

            foreach ($invoices as $invoice) {
                $invoiceTotal = app(InvoiceService::class)->calculateTotal($invoice->id);
                $totalRevenue += $invoiceTotal;
            }

            // Apply commission rules
            $commissionPercentage = $rules['percentage'] ?? 10;
            $commissionAmount = $totalRevenue * ($commissionPercentage / 100);

            // Apply minimum and maximum limits
            if (isset($rules['minimum']) && $commissionAmount < $rules['minimum']) {
                $commissionAmount = $rules['minimum'];
            }
            
            if (isset($rules['maximum']) && $commissionAmount > $rules['maximum']) {
                $commissionAmount = $rules['maximum'];
            }

            $commission = $this->create([
                'driver_id' => $driverId,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'total_revenue' => $totalRevenue,
                'commission_percentage' => $commissionPercentage,
                'commission_amount' => $commissionAmount,
                'status' => 'calculated',
                'calculated_at' => now()
            ]);

            event(new CommissionCalculated($commission));

            DB::commit();

            return $commission;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve commission
     */
    public function approve(int $commissionId, int $approvedBy): Model
    {
        return $this->update($commissionId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now()
        ]);
    }

    /**
     * Pay commission
     */
    public function pay(int $commissionId, array $paymentData): Model
    {
        return $this->update($commissionId, [
            'status' => 'paid',
            'paid_at' => $paymentData['paid_at'] ?? now(),
            'payment_reference' => $paymentData['reference'] ?? null,
            'payment_method' => $paymentData['method'] ?? null
        ]);
    }

    /**
     * Get commissions by driver
     */
    public function getByDriver(int $driverId)
    {
        return $this->repository->getByDriver($driverId);
    }

    /**
     * Get commissions by status
     */
    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    /**
     * Get unpaid commissions
     */
    public function getUnpaid()
    {
        return $this->repository->getUnpaid();
    }

    /**
     * Get total commission for a period
     */
    public function getTotalForPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        return $this->repository->getTotalForPeriod($startDate, $endDate);
    }
}
