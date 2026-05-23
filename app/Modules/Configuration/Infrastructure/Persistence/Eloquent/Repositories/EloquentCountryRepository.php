<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use App\Support\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Repositories\CountryRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CountryModel;

class EloquentCountryRepository extends EloquentRepository implements CountryRepositoryInterface
{
    public function __construct(CountryModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, array $with = []): ?Model
    {
        return $this->query($with)->where('code', $code)->first();
    }

    public function findByName(string $name, array $with = []): ?Model
    {
        return $this->query($with)->where('name', $name)->first();
    }
}
