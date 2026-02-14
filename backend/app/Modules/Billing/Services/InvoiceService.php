<?php

namespace App\Modules\Billing\Services;

use App\Core\BaseService;
use App\Modules\Billing\Repositories\InvoiceRepository;
use App\Modules\Billing\Repositories\InvoiceItemRepository;
use App\Modules\Billing\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;

class InvoiceService extends BaseService
{
    protected InvoiceRepository $repository;
    protected InvoiceItemRepository $itemRepository;
    protected PaymentRepository $paymentRepository;

    public function __construct(
        InvoiceRepository $repository,
        InvoiceItemRepository $itemRepository,
        PaymentRepository $paymentRepository
    ) {
        $this->repository = $repository;
        $this->itemRepository = $itemRepository;
        $this->paymentRepository = $paymentRepository;
    }

    public function createInvoice(array $data)
    {
        return $this->transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $invoice = $this->repository->create($data);

            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['tenant_id'] = $data['tenant_id'];
                    $item['invoice_id'] = $invoice->id;
                    $this->itemRepository->create($item);
                }
            }

            $this->logActivity('Invoice created', ['invoice_id' => $invoice->id]);
            return $invoice->load('items');
        });
    }

    public function updateInvoice(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $result = $this->repository->update($id, $data);

            if ($result && $items !== null) {
                $this->itemRepository->deleteByInvoice($id);
                foreach ($items as $item) {
                    $invoice = $this->repository->find($id);
                    $item['tenant_id'] = $invoice->tenant_id;
                    $item['invoice_id'] = $id;
                    $this->itemRepository->create($item);
                }
            }

            if ($result) {
                $this->logActivity('Invoice updated', ['invoice_id' => $id]);
            }
            return $result;
        });
    }

    public function getInvoice(int $id)
    {
        return $this->repository->with(['items.sku', 'customer', 'payments', 'tenant'])->find($id);
    }

    public function getAllInvoices()
    {
        return $this->repository->with(['items', 'customer', 'payments'])->all();
    }

    public function deleteInvoice(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            if ($result) {
                $this->logActivity('Invoice deleted', ['invoice_id' => $id]);
            }
            return $result;
        });
    }

    public function sendInvoice(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }

            if ($invoice->status !== 'draft') {
                return false;
            }

            $result = $this->repository->update($id, ['status' => 'sent']);
            
            if ($result) {
                $this->logActivity('Invoice sent', ['invoice_id' => $id]);
            }
            
            return $result;
        });
    }

    public function markAsPaid(int $id, array $paymentData = []): bool
    {
        return $this->transaction(function () use ($id, $paymentData) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }

            if ($invoice->status === 'paid' || $invoice->status === 'cancelled') {
                return false;
            }

            if (!empty($paymentData)) {
                $paymentData['tenant_id'] = $invoice->tenant_id;
                $paymentData['invoice_id'] = $id;
                $paymentData['amount'] = $paymentData['amount'] ?? $invoice->balance;
                $paymentData['payment_date'] = $paymentData['payment_date'] ?? now();
                $paymentData['status'] = 'completed';
                
                $this->paymentRepository->create($paymentData);
            }

            $totalPaid = $this->paymentRepository->getByInvoice($id)->sum('amount');
            $balance = $invoice->total_amount - $totalPaid;

            $updateData = [
                'paid_amount' => $totalPaid,
                'balance' => $balance,
            ];

            if ($balance <= 0) {
                $updateData['status'] = 'paid';
            }

            $result = $this->repository->update($id, $updateData);
            
            if ($result) {
                $this->logActivity('Invoice marked as paid', ['invoice_id' => $id]);
            }
            
            return $result;
        });
    }

    public function getInvoicesByCustomer(int $customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }

    public function getInvoicesByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getOverdueInvoices(int $tenantId)
    {
        return $this->repository->getOverdueInvoices($tenantId);
    }
}
