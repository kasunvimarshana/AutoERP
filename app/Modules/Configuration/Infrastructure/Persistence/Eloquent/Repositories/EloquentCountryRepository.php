<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CountryModel;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentCountryRepository extends EloquentRepository implements CountryRepositoryInterface
{
    public function __construct(CountryModel $model)
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
