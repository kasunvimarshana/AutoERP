<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;

final class EloquentAuditLogRepository extends EloquentRepository implements AuditLogRepositoryInterface
{
    public function __construct(AuditLogModel $model)
    {
        parent::__construct($model);
    }
}