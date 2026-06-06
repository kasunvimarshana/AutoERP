<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Models\TimezoneModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentTimezoneRepository extends EloquentRepository implements TimezoneRepositoryInterface
{
    public function __construct(TimezoneModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name): ?DataRecord
    {
        $model = $this->query()->where('name', $name)->first();

        if (! $model instanceof Model) {
            return null;
        }

        return $this->toRecord($model);
    }
}
