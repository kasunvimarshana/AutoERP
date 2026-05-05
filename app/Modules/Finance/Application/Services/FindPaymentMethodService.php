<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Application\Contracts\FindPaymentMethodServiceInterface;
use Modules\Finance\Domain\Entities\PaymentMethod;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentMethodRepositoryInterface;

class FindPaymentMethodService implements FindPaymentMethodServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'type', 'is_active'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'type', 'created_at', 'updated_at'];

    public function __construct(private readonly PaymentMethodRepositoryInterface $paymentMethodRepository) {}

    public function find(mixed $id): ?PaymentMethod
    {
        return $this->paymentMethodRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->paymentMethodRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && $field === 'name') {
                $repository->where($field, 'like', '%'.$value.'%');

                continue;
            }

            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
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
