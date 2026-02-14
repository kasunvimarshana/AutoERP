<?php

namespace App\Modules\Invoice\Services;

use App\Base\BaseService;
use App\Modules\Invoice\Repositories\InvoiceRepository;
use App\Modules\Invoice\Repositories\InvoiceItemRepository;
use App\Modules\Invoice\DTOs\InvoiceDTO;
use App\Modules\Invoice\DTOs\InvoiceItemDTO;
use App\Modules\Invoice\Events\InvoiceCreated;
use App\Modules\Invoice\Events\InvoiceUpdated;
use App\Modules\Invoice\Events\InvoiceDeleted;
use App\Modules\Invoice\Events\InvoiceSent;
use App\Modules\Invoice\Events\InvoicePaid;
use App\Modules\Invoice\Events\InvoiceCancelled;
use App\Modules\Invoice\Events\PaymentRecorded;
use App\Modules\Order\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService extends BaseService
{
    protected InvoiceItemRepository $invoiceItemRepository;
    protected OrderRepository $orderRepository;

    public function __construct(
        InvoiceRepository $repository,
        InvoiceItemRepository $invoiceItemRepository,
        OrderRepository $orderRepository
    ) {
        parent::__construct($repository);
        $this->invoiceItemRepository = $invoiceItemRepository;
        $this->orderRepository = $orderRepository;
    }

    public function createInvoice(InvoiceDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($dto) {
            $data = $dto->toArray();
            
            if (empty($data['balance_due'])) {
                $data['balance_due'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);
            }
            
            $invoice = $this->repository->create($data);
            
            Event::dispatch(new InvoiceCreated($invoice));
            
            return $invoice;
        });
    }

    public function createFromOrder(string $orderId): Model
    {
        return $this->executeInTransaction(function () use ($orderId) {
            $order = $this->orderRepository->find($orderId, ['*'], ['orderItems', 'customer']);
            
            if (!$order) {
                throw new \Exception('Order not found');
            }

            $existingInvoice = $this->repository->findByOrder($orderId);
            if ($existingInvoice) {
                throw new \Exception('Invoice already exists for this order');
            }

            $invoiceData = [
                'tenant_id' => $order->tenant_id,
                'branch_id' => $order->branch_id,
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => 'draft',
                'subtotal' => $order->total_amount,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->grand_total,
                'paid_amount' => 0.00,
                'balance_due' => $order->grand_total,
                'payment_status' => 'unpaid',
                'notes' => $order->notes,
                'settings' => $order->settings ?? [],
            ];

            $invoice = $this->repository->create($invoiceData);

            foreach ($order->orderItems as $orderItem) {
                $this->invoiceItemRepository->create([
                    'invoice_id' => $invoice->id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'description' => $orderItem->notes ?? '',
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'tax_rate' => $orderItem->tax_rate,
                    'discount_amount' => $orderItem->discount_amount,
                    'line_total' => $orderItem->line_total,
                ]);
            }

            Event::dispatch(new InvoiceCreated($invoice));
            
            return $invoice;
        });
    }

    public function updateInvoice(string $id, InvoiceDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($id, $dto) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }
            
            $data = $dto->toArray();
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $invoice->refresh();
                Event::dispatch(new InvoiceUpdated($invoice));
            }
            
            return $result;
        });
    }

    public function deleteInvoice(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }
            
            $result = $this->repository->delete($id);
            
            if ($result) {
                Event::dispatch(new InvoiceDeleted($invoice));
            }
            
            return $result;
        });
    }

    public function sendInvoice(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }

            if (!$invoice->canBeSent()) {
                throw new \Exception('Invoice cannot be sent in its current status');
            }
            
            $result = $this->repository->update($id, ['status' => 'sent']);
            
            if ($result) {
                $invoice->refresh();
                Event::dispatch(new InvoiceSent($invoice));
            }
            
            return $result;
        });
    }

    public function cancelInvoice(string $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }

            if (!$invoice->canBeCancelled()) {
                throw new \Exception('Invoice cannot be cancelled in its current status');
            }
            
            $result = $this->repository->update($id, ['status' => 'cancelled']);
            
            if ($result) {
                $invoice->refresh();
                Event::dispatch(new InvoiceCancelled($invoice));
            }
            
            return $result;
        });
    }

    public function recordPayment(string $id, float $amount): bool
    {
        return $this->executeInTransaction(function () use ($id, $amount) {
            $invoice = $this->repository->find($id);
            
            if (!$invoice) {
                return false;
            }

            if (!$invoice->canReceivePayment()) {
                throw new \Exception('Invoice cannot receive payment in its current status');
            }

            if ($amount <= 0) {
                throw new \Exception('Payment amount must be greater than zero');
            }

            $newPaidAmount = $invoice->paid_amount + $amount;
            
            if ($newPaidAmount > $invoice->total_amount) {
                throw new \Exception('Payment amount exceeds invoice total');
            }

            $invoice->paid_amount = $newPaidAmount;
            $invoice->updatePaymentStatus();
            $invoice->save();
            
            Event::dispatch(new PaymentRecorded($invoice, $amount));
            
            if ($invoice->isFullyPaid()) {
                Event::dispatch(new InvoicePaid($invoice));
            }
            
            return true;
        });
    }

    public function addInvoiceItem(string $invoiceId, InvoiceItemDTO $dto): Model
    {
        return $this->executeInTransaction(function () use ($invoiceId, $dto) {
            $invoice = $this->repository->find($invoiceId);
            
            if (!$invoice) {
                throw new \Exception('Invoice not found');
            }
            
            $data = $dto->toArray();
            $data['invoice_id'] = $invoiceId;
            
            if (!isset($data['line_total']) || $data['line_total'] == 0) {
                $subtotal = $data['quantity'] * $data['unit_price'];
                $afterDiscount = $subtotal - ($data['discount_amount'] ?? 0);
                $taxAmount = $afterDiscount * ($data['tax_rate'] ?? 0);
                $data['line_total'] = $afterDiscount + $taxAmount;
            }
            
            $invoiceItem = $this->invoiceItemRepository->create($data);
            
            $this->recalculateInvoiceTotals($invoiceId);
            
            return $invoiceItem;
        });
    }

    public function updateInvoiceItem(string $itemId, InvoiceItemDTO $dto): bool
    {
        return $this->executeInTransaction(function () use ($itemId, $dto) {
            $invoiceItem = $this->invoiceItemRepository->find($itemId);
            
            if (!$invoiceItem) {
                return false;
            }
            
            $data = $dto->toArray();
            
            if (!isset($data['line_total']) || $data['line_total'] == 0) {
                $quantity = $data['quantity'] ?? $invoiceItem->quantity;
                $unitPrice = $data['unit_price'] ?? $invoiceItem->unit_price;
                $discountAmount = $data['discount_amount'] ?? $invoiceItem->discount_amount;
                $taxRate = $data['tax_rate'] ?? $invoiceItem->tax_rate;
                
                $subtotal = $quantity * $unitPrice;
                $afterDiscount = $subtotal - $discountAmount;
                $taxAmount = $afterDiscount * $taxRate;
                $data['line_total'] = $afterDiscount + $taxAmount;
            }
            
            $result = $this->invoiceItemRepository->update($itemId, $data);
            
            if ($result) {
                $this->recalculateInvoiceTotals($invoiceItem->invoice_id);
            }
            
            return $result;
        });
    }

    public function removeInvoiceItem(string $itemId): bool
    {
        return $this->executeInTransaction(function () use ($itemId) {
            $invoiceItem = $this->invoiceItemRepository->find($itemId);
            
            if (!$invoiceItem) {
                return false;
            }
            
            $invoiceId = $invoiceItem->invoice_id;
            
            $result = $this->invoiceItemRepository->delete($itemId);
            
            if ($result) {
                $this->recalculateInvoiceTotals($invoiceId);
            }
            
            return $result;
        });
    }

    public function calculateInvoiceTotal(string $invoiceId): array
    {
        $invoice = $this->repository->find($invoiceId, ['*'], ['invoiceItems']);
        
        if (!$invoice) {
            return [];
        }
        
        $subtotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        
        foreach ($invoice->invoiceItems as $item) {
            $itemSubtotal = $item->quantity * $item->unit_price;
            $subtotal += $itemSubtotal;
            $discountAmount += $item->discount_amount;
            
            $afterDiscount = $itemSubtotal - $item->discount_amount;
            $taxAmount += $afterDiscount * $item->tax_rate;
        }
        
        $totalAmount = $subtotal - $discountAmount + $taxAmount;
        $balanceDue = $totalAmount - $invoice->paid_amount;
        
        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'balance_due' => round($balanceDue, 2),
        ];
    }

    protected function recalculateInvoiceTotals(string $invoiceId): void
    {
        $totals = $this->calculateInvoiceTotal($invoiceId);
        
        if (!empty($totals)) {
            $invoice = $this->repository->find($invoiceId);
            $this->repository->update($invoiceId, $totals);
            
            $invoice->refresh();
            $invoice->updatePaymentStatus();
            $invoice->save();
        }
    }

    public function generatePDF(string $id): string
    {
        $invoice = $this->repository->find($id, ['*'], ['tenant', 'branch', 'customer', 'invoiceItems.productVariant']);
        
        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        
        $filename = "invoice-{$invoice->invoice_number}.pdf";
        $path = "invoices/{$invoice->tenant_id}/{$filename}";
        
        Storage::put($path, $pdf->output());
        
        return Storage::url($path);
    }

    protected function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = $this->repository->getModel()
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        
        return "INV-{$year}-{$newNumber}";
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Model
    {
        return $this->repository->findByInvoiceNumber($invoiceNumber);
    }

    public function findByCustomer(string $customerId)
    {
        return $this->repository->findByCustomer($customerId);
    }

    public function findByOrder(string $orderId)
    {
        return $this->repository->findByOrder($orderId);
    }

    public function findByTenant(string $tenantId)
    {
        return $this->repository->findByTenant($tenantId);
    }

    public function findByBranch(string $branchId)
    {
        return $this->repository->findByBranch($branchId);
    }

    public function getByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function getByPaymentStatus(string $paymentStatus)
    {
        return $this->repository->getByPaymentStatus($paymentStatus);
    }

    public function getOverdue()
    {
        return $this->repository->getOverdue();
    }

    public function getByDateRange(string $from, string $to)
    {
        return $this->repository->getByDateRange($from, $to);
    }
}
