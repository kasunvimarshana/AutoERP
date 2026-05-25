<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\EmployeeDocumentRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeDocumentModel;

final class EloquentEmployeeDocumentRepository extends EloquentRepository implements EmployeeDocumentRepositoryInterface
{
    public function __construct(EmployeeDocumentModel $model)
    {
        parent::__construct($model);
    }
}