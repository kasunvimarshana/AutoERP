<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Sales\Enums\QuotationStatus;
use Modules\Sales\Exceptions\QuotationNotFoundException;
use Modules\Sales\Models\Quotation;

class QuotationRepository extends BaseRepository
{
    public function __construct(Quotation $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return Quotation::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return QuotationNotFoundException::class;
    }

    public function findByCode(string $code): ?Quotation
    {
        return $this->model->where('quotation_code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Quotation
    {
        $quotation = $this->findByCode($code);

        if (! $quotation) {
            throw new QuotationNotFoundException("Quotation with code {$code} not found");
        }

        return $quotation;
    }

    public function getExpired()
    {
        return $this->model->expired()->get();
    }

    public function getByStatus(QuotationStatus $status, int $perPage = 15)
    {
        return $this->model->ofStatus($status)->latest()->paginate($perPage);
    }

    public function getByCustomer(string $customerId, int $perPage = 15)
    {
        return $this->model->forCustomer($customerId)->latest()->paginate($perPage);
    }

    public function getFiltered(array $filters, int $perPage = 15)
    {
        $query = $this->model->newQuery()
            ->with(['organization', 'customer', 'items.product']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('quotation_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('quotation_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('quotation_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('quotation_date')->paginate($perPage);
    }
}
