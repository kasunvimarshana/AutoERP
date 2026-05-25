<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Extension\Application\Repositories\AttachmentRepositoryInterface;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\AttachmentModel;

final class EloquentAttachmentRepository extends EloquentRepository implements AttachmentRepositoryInterface
{
    public function __construct(AttachmentModel $model)
    {
        parent::__construct($model);
    }
}