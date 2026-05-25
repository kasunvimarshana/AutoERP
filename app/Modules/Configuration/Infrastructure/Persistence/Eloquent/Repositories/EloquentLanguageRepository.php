<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Application\Repositories\LanguageRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\LanguageModel;

class EloquentLanguageRepository extends EloquentRepository implements LanguageRepositoryInterface
{
    public function __construct(LanguageModel $model)
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

