<?php

namespace App\Modules\InvoicingManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InvoicingManagement\Repositories\InvoiceItemRepository;
use Illuminate\Database\Eloquent\Model;

class InvoiceItemService extends BaseService
{
    public function __construct(InvoiceItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Get items by invoice
     */
    public function getByInvoice(int $invoiceId)
    {
        return $this->repository->getByInvoice($invoiceId);
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal(int $itemId): float
    {
        $item = $this->repository->findOrFail($itemId);
        $subtotal = $item->quantity * $item->unit_price;
        $taxAmount = $subtotal * ($item->tax_percentage ?? 0) / 100;
        $discountAmount = $subtotal * ($item->discount_percentage ?? 0) / 100;
        
        return $subtotal + $taxAmount - $discountAmount;
    }

    /**
     * Update quantity
     */
    public function updateQuantity(int $itemId, float $quantity): Model
    {
        return $this->update($itemId, ['quantity' => $quantity]);
    }

    /**
     * Update unit price
     */
    public function updateUnitPrice(int $itemId, float $unitPrice): Model
    {
        return $this->update($itemId, ['unit_price' => $unitPrice]);
    }

    /**
     * Apply discount
     */
    public function applyDiscount(int $itemId, float $discountPercentage): Model
    {
        return $this->update($itemId, ['discount_percentage' => $discountPercentage]);
    }

    /**
     * Remove discount
     */
    public function removeDiscount(int $itemId): Model
    {
        return $this->update($itemId, ['discount_percentage' => 0]);
    }
}
