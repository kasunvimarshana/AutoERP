<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\CRM\Domain\Contracts\Repositories\ContactRepositoryInterface;
use Modules\CRM\Infrastructure\Persistence\Eloquent\Models\ContactModel;

class EloquentContactRepository extends EloquentRepository implements ContactRepositoryInterface
{
    public function __construct(ContactModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find all contacts for a given polymorphic owner type and ID.
     */
    public function findByContactable(string $type, string $id): Collection
    {
        return $this->model->newQuery()
            ->where('contactable_type', $type)
            ->where('contactable_id', $id)
            ->get();
    }
}
