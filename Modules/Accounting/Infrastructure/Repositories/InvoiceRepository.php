<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\InvoiceModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class InvoiceRepository extends BaseEloquentRepository implements InvoiceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new InvoiceModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = InvoiceModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }
        if (! empty($filters['partner_type'])) {
            $query->where('partner_type', $filters['partner_type']);
        }
        if (! empty($filters['invoice_type'])) {
            $query->where('invoice_type', $filters['invoice_type']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function nextNumber(string $tenantId): string
    {
        $count = InvoiceModel::withTrashed()->where('tenant_id', $tenantId)->count();
        return 'INV-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }

    public function nextCreditNoteNumber(string $tenantId): string
    {
        $count = InvoiceModel::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('invoice_type', 'credit_note')
            ->count();
        return 'CN-' . date('Y') . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
    }
}
