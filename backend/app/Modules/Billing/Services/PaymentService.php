<?php

namespace App\Modules\Billing\Services;

use App\Core\BaseService;
use App\Modules\Billing\Repositories\PaymentRepository;
use App\Modules\Billing\Repositories\InvoiceRepository;

class PaymentService extends BaseService
{
    protected PaymentRepository $repository;
    protected InvoiceRepository $invoiceRepository;

    public function __construct(
        PaymentRepository $repository,
        InvoiceRepository $invoiceRepository
    ) {
        $this->repository = $repository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createPayment(array $data)
    {
        return $this->transaction(function () use ($data) {
            $payment = $this->repository->create($data);

            $invoice = $this->invoiceRepository->find($data['invoice_id']);
            if ($invoice) {
                $totalPaid = $this->repository->getByInvoice($invoice->id)->sum('amount');
                $balance = $invoice->total_amount - $totalPaid;

                $updateData = [
                    'paid_amount' => $totalPaid,
                    'balance' => $balance,
                ];

                if ($balance <= 0) {
                    $updateData['status'] = 'paid';
                }

                $this->invoiceRepository->update($invoice->id, $updateData);
            }

            $this->logActivity('Payment created', ['payment_id' => $payment->id]);
            return $payment;
        });
    }

    public function updatePayment(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            if ($result) {
                $this->logActivity('Payment updated', ['payment_id' => $id]);
            }
            return $result;
        });
    }

    public function getPayment(int $id)
    {
        return $this->repository->with(['invoice', 'tenant'])->find($id);
    }

    public function getAllPayments()
    {
        return $this->repository->with(['invoice'])->all();
    }

    public function deletePayment(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $payment = $this->repository->find($id);
            
            $result = $this->repository->delete($id);
            
            if ($result && $payment) {
                $invoice = $this->invoiceRepository->find($payment->invoice_id);
                if ($invoice) {
                    $totalPaid = $this->repository->getByInvoice($invoice->id)->sum('amount');
                    $balance = $invoice->total_amount - $totalPaid;

                    $updateData = [
                        'paid_amount' => $totalPaid,
                        'balance' => $balance,
                    ];

                    if ($balance > 0 && $invoice->status === 'paid') {
                        $updateData['status'] = 'sent';
                    }

                    $this->invoiceRepository->update($invoice->id, $updateData);
                }
                
                $this->logActivity('Payment deleted', ['payment_id' => $id]);
            }
            return $result;
        });
    }

    public function getPaymentsByInvoice(int $invoiceId)
    {
        return $this->repository->getByInvoice($invoiceId);
    }

    public function getPaymentsByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }
}
