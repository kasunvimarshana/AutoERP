<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\Quotation;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class QuotationRepository extends BaseRepository
{
    protected function model(): string
    {
        return Quotation::class;
    }

    public function findByQuotationNumber(string $quotationNumber): ?Quotation
    {
        return $this->model->where('quotation_number', $quotationNumber)->first();
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        $query = $this->model->where('customer_id', $customerId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['valid_only']) && $filters['valid_only']) {
            $query->where('valid_until', '>=', now());
        }

        return $query->with(['items.product', 'customer'])->get();
    }

    public function findExpiredQuotations(): Collection
    {
        return $this->model
            ->where('valid_until', '<', now())
            ->where('status', '!=', 'cancelled')
            ->whereNull('converted_to_sales_order_id')
            ->with(['items.product', 'customer'])
            ->get();
    }

    public function generateQuotationNumber(): string
    {
        $lastQuotation = $this->model->orderBy('id', 'desc')->first();
        $lastNumber = $lastQuotation ? intval(substr($lastQuotation->quotation_number, 5)) : 0;

        return 'QUOT-'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}
