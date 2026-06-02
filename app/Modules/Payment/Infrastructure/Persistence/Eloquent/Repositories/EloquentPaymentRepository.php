<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

final class EloquentPaymentRepository extends EloquentRepository implements PaymentRepositoryInterface
{
    public function __construct(PaymentModel $model)
    {
        parent::__construct($model);
    }

    public function page(
        array $criteria,
        int $perPage,
        int $page,
        array $with = [],
    ): PagedResult {
        $search = trim((string) ($criteria['search'] ?? ''));
        unset($criteria['search']);

        $query = $this->applyCriteria($this->query($with), $criteria);

        if ($search !== '') {
            $query->where(function ($nested) use ($search): void {
                $nested
                    ->where('payment_number', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%');
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $model) {
            $items[] = $this->toRecord($model);
        }

        return new PagedResult(
            $items,
            $paginator->total(),
            $paginator->currentPage(),
            $paginator->perPage(),
        );
    }
}
