<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\Payment\Repositories\PaymentRepositoryInterface;
use App\Modules\Payment\Repositories\PaymentMethodRepositoryInterface;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService extends BaseService
{
    protected PaymentMethodRepositoryInterface $paymentMethodRepository;

    /**
     * PaymentService constructor.
     *
     * @param PaymentRepositoryInterface $repository
     * @param PaymentMethodRepositoryInterface $paymentMethodRepository
     */
    public function __construct(
        PaymentRepositoryInterface $repository,
        PaymentMethodRepositoryInterface $paymentMethodRepository
    ) {
        parent::__construct($repository);
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * Create a new payment
     *
     * @param array $data
     * @return Payment
     * @throws ServiceException
     */
    public function create(array $data): Payment
    {
        DB::beginTransaction();
        try {
            // Generate payment number if not provided
            if (empty($data['payment_number'])) {
                $data['payment_number'] = $this->generatePaymentNumber($data['payment_type'] ?? 'received');
            }

            // Set default status
            $data['status'] = $data['status'] ?? 'pending';
            $data['reconciliation_status'] = $data['reconciliation_status'] ?? 'unreconciled';

            // Calculate base amount if exchange rate is provided
            if (!empty($data['exchange_rate']) && !empty($data['amount'])) {
                $data['base_amount'] = bcmul((string)$data['amount'], (string)$data['exchange_rate'], 2);
            } else {
                $data['base_amount'] = $data['amount'];
                $data['exchange_rate'] = 1.0000;
            }

            $payment = $this->repository->create($data);

            // Handle allocations if provided
            if (!empty($data['allocations']) && is_array($data['allocations'])) {
                $this->allocatePayment($payment, $data['allocations']);
            }

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create payment: ' . $e->getMessage());
        }
    }

    /**
     * Update a payment
     *
     * @param int $id
     * @param array $data
     * @return Payment
     * @throws ServiceException
     */
    public function update(int $id, array $data): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findById($id);
            if (!$payment) {
                throw new ServiceException('Payment not found');
            }

            // Recalculate base amount if needed
            if (isset($data['amount']) || isset($data['exchange_rate'])) {
                $amount = $data['amount'] ?? $payment->amount;
                $exchangeRate = $data['exchange_rate'] ?? $payment->exchange_rate;
                $data['base_amount'] = bcmul((string)$amount, (string)$exchangeRate, 2);
            }

            $payment = $this->repository->update($id, $data);

            // Handle allocations if provided
            if (isset($data['allocations']) && is_array($data['allocations'])) {
                // Delete existing allocations
                $payment->allocations()->delete();
                // Create new allocations
                $this->allocatePayment($payment, $data['allocations']);
            }

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to update payment: ' . $e->getMessage());
        }
    }

    /**
     * Allocate payment to invoices/orders
     *
     * @param Payment $payment
     * @param array $allocations
     * @return void
     * @throws ServiceException
     */
    public function allocatePayment(Payment $payment, array $allocations): void
    {
        $totalAllocated = '0.00';

        foreach ($allocations as $allocation) {
            if (empty($allocation['allocatable_type']) || empty($allocation['allocatable_id']) || empty($allocation['amount'])) {
                throw new ServiceException('Invalid allocation data');
            }

            $allocationData = [
                'tenant_id' => $payment->tenant_id,
                'payment_id' => $payment->id,
                'allocatable_type' => $allocation['allocatable_type'],
                'allocatable_id' => $allocation['allocatable_id'],
                'amount' => $allocation['amount'],
                'currency_code' => $allocation['currency_code'] ?? $payment->currency_code,
                'exchange_rate' => $allocation['exchange_rate'] ?? $payment->exchange_rate,
                'base_amount' => bcmul((string)$allocation['amount'], (string)($allocation['exchange_rate'] ?? $payment->exchange_rate), 2),
                'notes' => $allocation['notes'] ?? null,
            ];

            PaymentAllocation::create($allocationData);

            $totalAllocated = bcadd($totalAllocated, (string)$allocation['amount'], 2);
        }

        // Validate total allocated doesn't exceed payment amount
        if (bccomp($totalAllocated, (string)$payment->amount, 2) > 0) {
            throw new ServiceException('Total allocated amount exceeds payment amount');
        }
    }

    /**
     * Reconcile payment
     *
     * @param int $id
     * @param array $data
     * @return Payment
     * @throws ServiceException
     */
    public function reconcile(int $id, array $data = []): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findById($id);
            if (!$payment) {
                throw new ServiceException('Payment not found');
            }

            if ($payment->reconciliation_status === 'reconciled') {
                throw new ServiceException('Payment is already reconciled');
            }

            $updateData = [
                'reconciliation_status' => 'reconciled',
                'reconciled_at' => now(),
                'reconciled_by' => auth()->id(),
            ];

            if (!empty($data['notes'])) {
                $updateData['notes'] = $payment->notes . "\n" . $data['notes'];
            }

            $payment = $this->repository->update($id, $updateData);

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to reconcile payment: ' . $e->getMessage());
        }
    }

    /**
     * Unreconcile payment
     *
     * @param int $id
     * @return Payment
     * @throws ServiceException
     */
    public function unreconcile(int $id): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findById($id);
            if (!$payment) {
                throw new ServiceException('Payment not found');
            }

            if ($payment->reconciliation_status === 'unreconciled') {
                throw new ServiceException('Payment is already unreconciled');
            }

            $updateData = [
                'reconciliation_status' => 'unreconciled',
                'reconciled_at' => null,
                'reconciled_by' => null,
            ];

            $payment = $this->repository->update($id, $updateData);

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to unreconcile payment: ' . $e->getMessage());
        }
    }

    /**
     * Complete payment
     *
     * @param int $id
     * @return Payment
     * @throws ServiceException
     */
    public function complete(int $id): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findById($id);
            if (!$payment) {
                throw new ServiceException('Payment not found');
            }

            if ($payment->status === 'completed') {
                throw new ServiceException('Payment is already completed');
            }

            $payment = $this->repository->update($id, ['status' => 'completed']);

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to complete payment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel payment
     *
     * @param int $id
     * @return Payment
     * @throws ServiceException
     */
    public function cancel(int $id): Payment
    {
        DB::beginTransaction();
        try {
            $payment = $this->repository->findById($id);
            if (!$payment) {
                throw new ServiceException('Payment not found');
            }

            if ($payment->status === 'cancelled') {
                throw new ServiceException('Payment is already cancelled');
            }

            if ($payment->status === 'completed' && $payment->reconciliation_status === 'reconciled') {
                throw new ServiceException('Cannot cancel a completed and reconciled payment');
            }

            $payment = $this->repository->update($id, ['status' => 'cancelled']);

            DB::commit();
            return $payment->load(['paymentMethod', 'allocations']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to cancel payment: ' . $e->getMessage());
        }
    }

    /**
     * Generate payment number
     *
     * @param string $type
     * @return string
     */
    protected function generatePaymentNumber(string $type = 'received'): string
    {
        $prefix = $type === 'paid' ? 'PMT' : 'RCP';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get payment statistics
     *
     * @param array $filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $query = Payment::query();

        if (!empty($filters['start_date'])) {
            $query->whereDate('payment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('payment_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['payment_type'])) {
            $query->where('payment_type', $filters['payment_type']);
        }

        return [
            'total_payments' => $query->count(),
            'total_amount' => (string)$query->sum('base_amount'),
            'completed_payments' => (clone $query)->where('status', 'completed')->count(),
            'pending_payments' => (clone $query)->where('status', 'pending')->count(),
            'cancelled_payments' => (clone $query)->where('status', 'cancelled')->count(),
            'reconciled_payments' => (clone $query)->where('reconciliation_status', 'reconciled')->count(),
            'unreconciled_payments' => (clone $query)->where('reconciliation_status', 'unreconciled')->count(),
        ];
    }
}
