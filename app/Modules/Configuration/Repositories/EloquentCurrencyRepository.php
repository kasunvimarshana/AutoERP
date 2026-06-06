<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

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
}
