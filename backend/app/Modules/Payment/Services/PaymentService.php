<?php

namespace App\Modules\Payment\Services;

use App\Base\BaseService;
use App\Modules\Payment\Repositories\PaymentRepository;
use App\Modules\Payment\Repositories\PaymentRefundRepository;
use App\Modules\Payment\DTOs\PaymentDTO;
use App\Modules\Payment\DTOs\PaymentRefundDTO;
use App\Modules\Payment\Events\PaymentCreated;
use App\Modules\Payment\Events\PaymentUpdated;
use App\Modules\Payment\Events\PaymentDeleted;
use App\Modules\Payment\Events\PaymentCompleted;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Events\RefundProcessed;
use App\Modules\Invoice\Repositories\InvoiceRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class PaymentService extends BaseService
{
    protected PaymentRefundRepository $refundRepository;
    protected InvoiceRepository $invoiceRepository;

    public function __construct(
        PaymentRepository $repository,
        PaymentRefundRepository $refundRepository,
        InvoiceRepository $invoiceRepository
    ) {
        parent::__construct($repository);
        $this->refundRepository = $refundRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createPayment(PaymentDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            $payment = $this->repository->create($data);
            
            Event::dispatch(new PaymentCreated($payment));
            
            return $payment;
        });
    }

    public function updatePayment(string $id, PaymentDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $payment->refresh();
                Event::dispatch(new PaymentUpdated($payment));
            }
            
            return $result;
        });
    }

    public function deletePayment(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new PaymentDeleted($payment));
            }
            
            return $result;
        });
    }

    public function completePayment(string $id, string $transactionId = null): bool
    {
        return $this->executeInTransaction(function () use ($id, $transactionId) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                return false;
            }

            if (!$payment->canBeCompleted()) {
                throw new \Exception('Payment cannot be completed in its current status');
            }

            $updateData = ['status' => 'completed'];
            if ($transactionId) {
                $updateData['transaction_id'] = $transactionId;
            }
            
            $result = $this->repository->update($id, $updateData);
            
            if ($result) {
                $payment->refresh();
                
                if ($payment->invoice_id) {
                    $this->updateInvoicePaymentStatus($payment->invoice_id);
                }
                
                Event::dispatch(new PaymentCompleted($payment));
            }
            
            return $result;
        });
    }

    public function failPayment(string $id, string $reason = null): bool
    {
        return $this->executeInTransaction(function () use ($id, $reason) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                return false;
            }

            $updateData = ['status' => 'failed'];
            if ($reason) {
                $updateData['notes'] = ($payment->notes ? $payment->notes . "\n" : '') . "Failed: " . $reason;
            }
            
            $result = $this->repository->update($id, $updateData);
            
            if ($result) {
                $payment->refresh();
                Event::dispatch(new PaymentFailed($payment));
            }
            
            return $result;
        });
    }

    public function cancelPayment(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                return false;
            }

            if (!$payment->canBeCancelled()) {
                throw new \Exception('Payment cannot be cancelled in its current status');
            }
            
            $result = $this->repository->update($id, ['status' => 'cancelled']);
            
            if ($result) {
                $payment->refresh();
            }
            
            return $result;
        });
    }

    public function refundPayment(string $id, PaymentRefundDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $payment = $this->repository->find($id);
            
            if (!$payment) {
                throw new \Exception('Payment not found');
            }

            if (!$payment->canBeRefunded()) {
                throw new \Exception('Payment cannot be refunded in its current status');
            }

            $remainingAmount = $payment->getRemainingAmount();
            if ($dto->amount > $remainingAmount) {
                throw new \Exception("Refund amount ({$dto->amount}) exceeds remaining amount ({$remainingAmount})");
            }

            $refundData = $dto->toArray();
            $refundData['payment_id'] = $payment->id;
            $refundData['status'] = 'pending';
            
            $refund = $this->refundRepository->create($refundData);
            
            Event::dispatch(new PaymentRefunded($refund));
            
            return $refund;
        });
    }

    public function processRefund(string $refundId): bool
    {
        return $this->executeInTransaction(function () use ($refundId) {
            $refund = $this->refundRepository->find($refundId);
            
            if (!$refund) {
                return false;
            }

            if (!$refund->canBeProcessed()) {
                throw new \Exception('Refund cannot be processed in its current status');
            }
            
            $result = $this->refundRepository->update($refundId, ['status' => 'completed']);
            
            if ($result) {
                $refund->refresh();
                $payment = $refund->payment;
                
                $totalRefunded = $payment->getTotalRefunded();
                if ($totalRefunded >= $payment->amount) {
                    $this->repository->update($payment->id, ['status' => 'refunded']);
                }
                
                if ($payment->invoice_id) {
                    $this->updateInvoicePaymentStatus($payment->invoice_id);
                }
                
                Event::dispatch(new RefundProcessed($refund));
            }
            
            return $result;
        });
    }

    public function recordPaymentForInvoice(string $invoiceId, PaymentDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($invoiceId, $dto) {
            $invoice = $this->invoiceRepository->find($invoiceId);
            
            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }

            if (!$invoice->canReceivePayment()) {
                throw new \Exception('Invoice cannot receive payment in its current status');
            }

            $data = $dto->toArray();
            $data['invoice_id'] = $invoiceId;
            $data['tenant_id'] = $invoice->tenant_id;
            $data['branch_id'] = $invoice->branch_id;
            $data['customer_id'] = $invoice->customer_id;
            
            $payment = $this->repository->create($data);
            
            if ($payment->status === 'completed') {
                $this->updateInvoicePaymentStatus($invoiceId);
            }
            
            Event::dispatch(new PaymentCreated($payment));
            
            return $payment;
        });
    }

    public function getPaymentSummary(string $from, string $to): array
    {
        $payments = $this->repository->getByDateRange($from, $to);
        
        $summary = [
            'total_payments' => $payments->count(),
            'total_amount' => 0,
            'completed_amount' => 0,
            'pending_amount' => 0,
            'refunded_amount' => 0,
            'by_method' => [],
            'by_status' => [
                'pending' => 0,
                'completed' => 0,
                'failed' => 0,
                'refunded' => 0,
                'cancelled' => 0,
            ],
        ];

        $methods = ['cash', 'card', 'bank_transfer', 'cheque', 'online', 'other'];
        foreach ($methods as $method) {
            $summary['by_method'][$method] = [
                'count' => 0,
                'amount' => 0,
            ];
        }

        foreach ($payments as $payment) {
            $summary['total_amount'] += $payment->amount;
            
            if ($payment->status === 'completed') {
                $summary['completed_amount'] += $payment->amount;
            } elseif ($payment->status === 'pending') {
                $summary['pending_amount'] += $payment->amount;
            } elseif ($payment->status === 'refunded') {
                $summary['refunded_amount'] += $payment->amount;
            }

            $summary['by_status'][$payment->status]++;
            
            if (isset($summary['by_method'][$payment->payment_method])) {
                $summary['by_method'][$payment->payment_method]['count']++;
                $summary['by_method'][$payment->payment_method]['amount'] += $payment->amount;
            }
        }

        $totalRefunds = $this->refundRepository->getTotalRefunds($from, $to);
        $summary['total_refunds'] = $totalRefunds;

        return $summary;
    }

    protected function updateInvoicePaymentStatus(string $invoiceId): void
    {
        $invoice = $this->invoiceRepository->find($invoiceId);
        
        if (!$invoice) {
            return;
        }

        $completedPayments = $this->repository->findByInvoice($invoiceId)
            ->where('status', 'completed');
        
        $totalPaid = $completedPayments->sum('amount');
        
        $completedRefunds = $this->refundRepository->model
            ->whereIn('payment_id', $completedPayments->pluck('id'))
            ->where('status', 'completed')
            ->sum('amount');
        
        $netPaid = $totalPaid - $completedRefunds;

        $this->invoiceRepository->update($invoiceId, [
            'paid_amount' => $netPaid,
        ]);

        $invoice->refresh();
        $invoice->updatePaymentStatus();
        $invoice->save();
    }
}
