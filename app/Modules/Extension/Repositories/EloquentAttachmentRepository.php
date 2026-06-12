<?php

declare(strict_types=1);

namespace Modules\Extension\Repositories;

use Modules\Core\Repositories\EloquentRepository;
use Modules\Extension\Models\AttachmentModel;

final class EloquentAttachmentRepository extends EloquentRepository implements AttachmentRepositoryInterface
{
    public function __construct(AttachmentModel $model)
    {
        parent::__construct($model);
    }
}
