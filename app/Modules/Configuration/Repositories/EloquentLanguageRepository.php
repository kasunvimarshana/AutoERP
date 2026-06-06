<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Configuration\Models\LanguageModel;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;

final class EloquentLanguageRepository extends EloquentRepository implements LanguageRepositoryInterface
{
    public function __construct(LanguageModel $model)
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
