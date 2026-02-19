<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\TotalCalculationService;
use Modules\Sales\Enums\OrderStatus;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Exceptions\InvalidQuotationStatusException;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Repositories\OrderRepository;
use Modules\Sales\Repositories\QuotationRepository;

/**
 * Quotation Service
 *
 * Handles business logic for quotations including lifecycle management,
 * status transitions, and conversion to orders.
 */
class QuotationService
{
    public function __construct(
        private QuotationRepository $quotationRepository,
        private OrderRepository $orderRepository,
        private CodeGeneratorService $codeGenerator,
        private TotalCalculationService $totalCalculationService
    ) {}

    /**
     * Create a new quotation with items.
     */
    public function createQuotation(array $data, array $items = []): Quotation
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate quotation code if not provided
            if (empty($data['quotation_code'])) {
                $data['quotation_code'] = $this->generateQuotationCode();
            }

            // Set default status
            $data['status'] = $data['status'] ?? QuotationStatus::DRAFT;

            // Calculate totals if items are provided
            if (! empty($items)) {
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Create quotation
            $quotation = $this->quotationRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['quotation_id'] = $quotation->id;
                    $quotation->items()->create($item);
                }
                $quotation->load('items');
            }

            return $quotation;
        });
    }

    /**
     * Update quotation and recalculate totals.
     */
    public function updateQuotation(string $id, array $data, ?array $items = null): Quotation
    {
        $quotation = $this->quotationRepository->findOrFail($id);

        if (! $quotation->canModify()) {
            throw new InvalidQuotationStatusException(
                "Quotation cannot be modified in {$quotation->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($quotation, $data, $items) {
            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $quotation->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    $item['quotation_id'] = $quotation->id;
                    $quotation->items()->create($item);
                }

                // Recalculate totals
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Update quotation
            return $this->quotationRepository->update($quotation->id, $data);
        });
    }

    /**
     * Send quotation to customer.
     */
    public function sendQuotation(string $id): Quotation
    {
        $quotation = $this->quotationRepository->findOrFail($id);

        if (! $quotation->canSend()) {
            throw new InvalidQuotationStatusException(
                "Quotation cannot be sent in {$quotation->status->value} status"
            );
        }

        return $this->quotationRepository->update($quotation->id, [
            'status' => QuotationStatus::SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Accept quotation.
     */
    public function acceptQuotation(string $id): Quotation
    {
        $quotation = $this->quotationRepository->findOrFail($id);

        if ($quotation->isExpired()) {
            throw new InvalidQuotationStatusException('Cannot accept expired quotation');
        }

        return $this->quotationRepository->update($quotation->id, [
            'status' => QuotationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Reject quotation.
     */
    public function rejectQuotation(string $id): Quotation
    {
        $quotation = $this->quotationRepository->findOrFail($id);

        return $this->quotationRepository->update($quotation->id, [
            'status' => QuotationStatus::REJECTED,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Convert quotation to order.
     */
    public function convertToOrder(string $id, array $orderData = []): array
    {
        $quotation = $this->quotationRepository->findOrFail($id);

        if (! $quotation->canConvert()) {
            throw new InvalidQuotationStatusException(
                "Quotation cannot be converted in {$quotation->status->value} status"
            );
        }

        if ($quotation->isExpired()) {
            throw new InvalidQuotationStatusException('Cannot convert expired quotation');
        }

        return TransactionHelper::execute(function () use ($quotation, $orderData) {
            // Generate order code
            $orderCode = $this->generateOrderCode();

            // Create order from quotation
            $order = $this->orderRepository->create(array_merge([
                'tenant_id' => $quotation->tenant_id,
                'organization_id' => $quotation->organization_id,
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->id,
                'order_code' => $orderCode,
                'reference' => $orderData['reference'] ?? $quotation->reference,
                'status' => OrderStatus::PENDING,
                'order_date' => now(),
                'subtotal' => $quotation->subtotal,
                'tax_amount' => $quotation->tax_amount,
                'discount_amount' => $quotation->discount_amount,
                'total_amount' => $quotation->total_amount,
                'paid_amount' => '0.00',
                'notes' => $orderData['notes'] ?? $quotation->notes,
                'terms_conditions' => $orderData['terms_conditions'] ?? $quotation->terms_conditions,
                'created_by' => $orderData['created_by'] ?? $quotation->created_by,
            ], $orderData));

            // Copy quotation items to order items
            foreach ($quotation->items as $quotationItem) {
                $order->items()->create([
                    'product_id' => $quotationItem->product_id,
                    'product_name' => $quotationItem->product_name,
                    'description' => $quotationItem->description,
                    'quantity' => $quotationItem->quantity,
                    'unit_price' => $quotationItem->unit_price,
                    'tax_rate' => $quotationItem->tax_rate,
                    'discount_amount' => $quotationItem->discount_amount,
                    'line_total' => $quotationItem->line_total,
                ]);
            }

            // Update quotation status
            $this->quotationRepository->update($quotation->id, [
                'status' => QuotationStatus::CONVERTED,
                'converted_at' => now(),
                'converted_order_id' => $order->id,
            ]);

            return [
                'quotation' => $quotation->fresh(),
                'order' => $order->load('items'),
            ];
        });
    }

    /**
     * Mark expired quotations.
     */
    public function deleteQuotation(string $id): void
    {
        TransactionHelper::execute(function () use ($id) {
            $this->quotationRepository->delete($id);
        });
    }

    public function markExpiredQuotations(): int
    {
        $expiredQuotations = Quotation::expired()->get();

        $count = 0;
        foreach ($expiredQuotations as $quotation) {
            $this->quotationRepository->update($quotation->id, [
                'status' => QuotationStatus::EXPIRED,
                'expired_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Calculate totals for quotation items.
     */
    private function calculateTotals(array $items, array $data): array
    {
        return $this->totalCalculationService->calculateLineTotals($items, $data);
    }

    /**
     * Generate unique quotation code.
     */
    private function generateQuotationCode(): string
    {
        $prefix = config('sales.quotation.prefix', 'QUO-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->quotationRepository->findByCode($code) !== null
        );
    }

    /**
     * Generate unique order code.
     */
    private function generateOrderCode(): string
    {
        $prefix = config('sales.order.prefix', 'ORD-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->orderRepository->findByCode($code) !== null
        );
    }
}
