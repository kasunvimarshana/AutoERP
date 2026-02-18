<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\BaseService;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Events\QuotationAccepted;
use Modules\Sales\Events\QuotationConverted;
use Modules\Sales\Events\QuotationCreated;
use Modules\Sales\Events\QuotationSent;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\QuotationItem;
use Modules\Sales\Repositories\CustomerRepository;
use Modules\Sales\Repositories\QuotationRepository;

class QuotationService extends BaseService
{
    public function __construct(
        protected QuotationRepository $repository,
        protected CustomerRepository $customerRepository,
        protected SalesOrderService $salesOrderService,
        \Modules\Core\Services\TenantContext $tenantContext
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get paginated quotations with filters.
     */
    public function getAll(array $filters = [], int $perPage = 15): array
    {
        $this->validateTenant();

        return $this->repository->getFiltered($filters, $perPage)->toArray();
    }

    /**
     * Find quotation by ID with items.
     */
    public function findById(int $id): ?Quotation
    {
        $this->validateTenant();

        return $this->repository->find($id)?->load('items', 'customer');
    }

    /**
     * Create new quotation with line items.
     */
    public function create(array $data): Quotation
    {
        $this->validateTenant();
        $this->validateQuotationData($data);

        return $this->transaction(function () use ($data) {
            // Auto-generate quote number if not provided
            if (empty($data['quote_number'])) {
                $data['quote_number'] = $this->repository->generateNextQuoteNumber();
            }

            $data['tenant_id'] = $this->getTenantId();
            $data['status'] = QuotationStatus::DRAFT;

            $items = $data['items'] ?? [];
            unset($data['items']);

            $quotation = $this->repository->create($data);

            // Add line items
            if (! empty($items)) {
                $this->addLineItems($quotation, $items);
            }

            $this->dispatchEvent(new QuotationCreated($quotation));
            $this->logActivity('created', $quotation);

            return $quotation->fresh('items');
        });
    }

    /**
     * Update existing quotation.
     */
    public function update(int $id, array $data): Quotation
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id);
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->status->canEdit()) {
            throw new \RuntimeException('Quotation cannot be edited in current status');
        }

        return $this->transaction(function () use ($id, $data, $quotation) {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $quotation = $this->repository->update($id, $data);

            // Update line items if provided
            if ($items !== null) {
                $quotation->items()->delete();
                $this->addLineItems($quotation, $items);
            }

            $this->logActivity('updated', $quotation);

            return $quotation->fresh('items');
        });
    }

    /**
     * Delete quotation.
     */
    public function delete(int $id): bool
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id);
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->status->canEdit()) {
            throw new \RuntimeException('Quotation cannot be deleted in current status');
        }

        return $this->transaction(function () use ($id, $quotation) {
            $this->logActivity('deleted', $quotation);

            return $this->repository->delete($id);
        });
    }

    /**
     * Send quotation to customer.
     */
    public function sendToCustomer(int $id): Quotation
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id);
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->status->canSend()) {
            throw new \RuntimeException('Quotation cannot be sent in current status');
        }

        return $this->transaction(function () use ($quotation) {
            $quotation->markAsSent();

            $this->dispatchEvent(new QuotationSent($quotation));
            $this->logActivity('sent', $quotation);

            return $quotation;
        });
    }

    /**
     * Accept quotation.
     */
    public function accept(int $id): Quotation
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id);
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->status->canRespond()) {
            throw new \RuntimeException('Quotation cannot be accepted in current status');
        }

        if (! $quotation->isValid()) {
            throw new \RuntimeException('Quotation has expired');
        }

        return $this->transaction(function () use ($quotation) {
            $quotation->accept();

            $this->dispatchEvent(new QuotationAccepted($quotation));
            $this->logActivity('accepted', $quotation);

            return $quotation;
        });
    }

    /**
     * Reject quotation.
     */
    public function reject(int $id, ?string $reason = null): Quotation
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id);
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->status->canRespond()) {
            throw new \RuntimeException('Quotation cannot be rejected in current status');
        }

        return $this->transaction(function () use ($quotation, $reason) {
            $quotation->reject();

            $this->logActivity('rejected', $quotation, ['reason' => $reason]);

            return $quotation;
        });
    }

    /**
     * Convert quotation to sales order.
     */
    public function convertToOrder(int $id): array
    {
        $this->validateTenant();

        $quotation = $this->repository->find($id)->load('items');
        if (! $quotation) {
            throw new \RuntimeException('Quotation not found');
        }

        if (! $quotation->canConvertToOrder()) {
            throw new \RuntimeException('Quotation cannot be converted to order');
        }

        return $this->transaction(function () use ($quotation) {
            // Create sales order from quotation
            $orderData = [
                'customer_id' => $quotation->customer_id,
                'order_date' => now(),
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'discount_amount' => $quotation->discount_amount,
                'total_amount' => $quotation->total_amount,
                'notes' => "Converted from Quotation #{$quotation->quote_number}",
                'items' => $quotation->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'tax_amount' => $item->tax_amount,
                        'discount_amount' => $item->discount_amount,
                    ];
                })->toArray(),
            ];

            $salesOrder = $this->salesOrderService->create($orderData);

            // Mark quotation as converted
            $this->repository->markAsConverted($quotation->id, $salesOrder->id);

            $this->dispatchEvent(new QuotationConverted($quotation, $salesOrder));
            $this->logActivity('converted_to_order', $quotation, [
                'order_id' => $salesOrder->id,
                'order_number' => $salesOrder->order_number,
            ]);

            return [
                'quotation' => $quotation->fresh(),
                'sales_order' => $salesOrder,
            ];
        });
    }

    /**
     * Process expired quotations.
     */
    public function processExpiredQuotations(): int
    {
        $this->validateTenant();

        $expiredQuotations = $this->repository->getExpiredQuotations();
        $count = 0;

        foreach ($expiredQuotations as $quotationData) {
            try {
                $quotation = $this->repository->find($quotationData['id']);
                $quotation->expire();
                $this->logActivity('auto_expired', $quotation);
                $count++;
            } catch (\Exception $e) {
                // Log error and continue
                \Log::error("Failed to expire quotation {$quotationData['id']}: ".$e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Add line items to quotation.
     */
    protected function addLineItems(Quotation $quotation, array $items): void
    {
        $lineNumber = 1;

        foreach ($items as $itemData) {
            $itemData['tenant_id'] = $this->getTenantId();
            $itemData['quotation_id'] = $quotation->id;
            $itemData['line_number'] = $lineNumber++;

            QuotationItem::create($itemData);
        }

        $quotation->recalculateTotals();
    }

    /**
     * Validate quotation data.
     */
    protected function validateQuotationData(array $data): void
    {
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'quote_date' => 'required|date',
            'valid_until' => 'nullable|date|after:quote_date',
            'currency' => 'nullable|string|size:3',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
