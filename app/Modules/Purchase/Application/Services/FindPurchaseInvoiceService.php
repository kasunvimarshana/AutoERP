<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Purchase\Application\Contracts\FindPurchaseInvoiceServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class FindPurchaseInvoiceService implements FindPurchaseInvoiceServiceInterface
{
    private const ALLOWED_FILTERS = ['tenant_id', 'supplier_id', 'org_unit_id', 'purchase_order_id', 'status', 'invoice_number'];

    private const ALLOWED_SORTS = ['id', 'invoice_number', 'invoice_date', 'status', 'created_at'];

    public function __construct(private readonly PurchaseInvoiceRepositoryInterface $repo) {}

    public function find(mixed $id): ?PurchaseInvoice
    {
        return $this->repo->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->repo->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        $perPage = $perPage ?? 15;

        return $repository->paginate($perPage, ['*'], 'page', $page);
    }

    public function execute(array $data = []): mixed
    {
        return $this->find($data['id'] ?? null);
    }

    private function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }
}
