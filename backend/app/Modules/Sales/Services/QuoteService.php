<?php

declare(strict_types=1);

namespace App\Modules\Sales\Services;

use App\Core\Services\BaseService;
use App\Modules\Sales\Repositories\QuoteRepositoryInterface;
use App\Modules\CRM\Repositories\CustomerRepositoryInterface;
use App\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuoteService extends BaseService
{
    protected CustomerRepositoryInterface $customerRepository;

    public function __construct(
        QuoteRepositoryInterface $repository,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($repository);
        $this->customerRepository = $customerRepository;
    }

    /**
     * Create a new quote with items.
     */
    public function createQuoteWithItems(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateQuoteTotals($items, $data);
            
            $quoteData = array_merge($data, [
                'quote_number' => $this->generateQuoteNumber(),
                'status' => $data['status'] ?? 'draft',
                'quote_date' => $data['quote_date'] ?? now(),
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'created_by' => Auth::id(),
            ]);

            $quote = $this->repository->create($quoteData);

            // Create quote items
            foreach ($items as $item) {
                $itemCalculations = $this->calculateItemTotals($item);
                
                $quote->items()->create(array_merge($item, [
                    'tenant_id' => $quote->tenant_id,
                    'discount_amount' => $itemCalculations['discount_amount'],
                    'tax_amount' => $itemCalculations['tax_amount'],
                    'subtotal' => $itemCalculations['subtotal'],
                    'total' => $itemCalculations['total'],
                ]));
            }

            return $quote->load(['items', 'customer', 'currency']);
        });
    }

    /**
     * Update quote with items.
     */
    public function updateQuoteWithItems(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $quote = $this->repository->findOrFail($id);

            if (!$quote->isEditable()) {
                throw new ServiceException('Quote cannot be edited in current status');
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals
            $calculations = $this->calculateQuoteTotals($items, $data);
            
            $quoteData = array_merge($data, [
                'subtotal' => $calculations['subtotal'],
                'discount_amount' => $calculations['discount_amount'],
                'tax_amount' => $calculations['tax_amount'],
                'total_amount' => $calculations['total_amount'],
                'updated_by' => Auth::id(),
            ]);

            $quote = $this->repository->update($id, $quoteData);

            // Update items
            if (!empty($items)) {
                $quote->items()->delete();
                
                foreach ($items as $item) {
                    $itemCalculations = $this->calculateItemTotals($item);
                    
                    $quote->items()->create(array_merge($item, [
                        'tenant_id' => $quote->tenant_id,
                        'discount_amount' => $itemCalculations['discount_amount'],
                        'tax_amount' => $itemCalculations['tax_amount'],
                        'subtotal' => $itemCalculations['subtotal'],
                        'total' => $itemCalculations['total'],
                    ]));
                }
            }

            return $quote->load(['items', 'customer', 'currency']);
        });
    }

    /**
     * Convert quote to sales order.
     */
    public function convertToSalesOrder(int $quoteId): mixed
    {
        return DB::transaction(function () use ($quoteId) {
            $quote = $this->repository->findOrFail($quoteId);

            if ($quote->isConverted()) {
                throw new ServiceException('Quote is already converted to sales order');
            }

            if ($quote->isExpired()) {
                throw new ServiceException('Quote has expired');
            }

            // Mark quote as converted
            $this->repository->update($quoteId, [
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $quote;
        });
    }

    /**
     * Calculate item totals.
     */
    protected function calculateItemTotals(array $item): array
    {
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPrice = (float) ($item['unit_price'] ?? 0);
        $subtotal = $quantity * $unitPrice;

        // Calculate discount
        $discountAmount = 0;
        if (isset($item['discount_type']) && isset($item['discount_value'])) {
            if ($item['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $item['discount_value'] / 100);
            } elseif ($item['discount_type'] === 'flat') {
                $discountAmount = (float) $item['discount_value'];
            }
        }

        $afterDiscount = $subtotal - $discountAmount;

        // Calculate tax
        $taxAmount = 0;
        if (isset($item['tax_percentage'])) {
            $taxAmount = $afterDiscount * ((float) $item['tax_percentage'] / 100);
        }

        $total = $afterDiscount + $taxAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate quote totals.
     */
    protected function calculateQuoteTotals(array $items, array $headerData): array
    {
        $subtotal = 0;
        $totalTax = 0;

        foreach ($items as $item) {
            $itemCalcs = $this->calculateItemTotals($item);
            $subtotal += $itemCalcs['subtotal'];
            $totalTax += $itemCalcs['tax_amount'];
        }

        // Apply header-level discount
        $discountAmount = 0;
        if (isset($headerData['discount_type']) && isset($headerData['discount_value'])) {
            if ($headerData['discount_type'] === 'percentage') {
                $discountAmount = $subtotal * ((float) $headerData['discount_value'] / 100);
            } elseif ($headerData['discount_type'] === 'flat') {
                $discountAmount = (float) $headerData['discount_value'];
            }
        }

        $shippingAmount = (float) ($headerData['shipping_amount'] ?? 0);
        $adjustmentAmount = (float) ($headerData['adjustment_amount'] ?? 0);

        $totalAmount = $subtotal - $discountAmount + $totalTax + $shippingAmount + $adjustmentAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Generate unique quote number.
     */
    protected function generateQuoteNumber(): string
    {
        $prefix = 'QT';
        $date = now()->format('Ymd');
        $lastQuote = $this->repository->getModel()
            ->where('quote_number', 'like', $prefix . $date . '%')
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            $lastNumber = (int) substr($lastQuote->quote_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}
