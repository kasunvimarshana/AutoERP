<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentCurrencyRepository extends EloquentRepository implements CurrencyRepositoryInterface
{
    public function __construct(CurrencyModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code): ?DataRecord
    {
        $model = $this->query()->where('code', $code)->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        unset($criteria['search']);

        parent::applyCriteria($query, $criteria);

        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
