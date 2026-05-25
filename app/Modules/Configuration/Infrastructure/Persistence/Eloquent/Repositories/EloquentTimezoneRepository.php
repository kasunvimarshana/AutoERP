<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Repositories\TimezoneRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\TimezoneModel;

class EloquentTimezoneRepository extends EloquentRepository implements TimezoneRepositoryInterface
{
    public function __construct(TimezoneModel $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }
}

