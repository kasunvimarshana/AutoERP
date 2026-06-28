<?php

declare(strict_types=1);

namespace Modules\Extension\Repositories;

use Modules\Core\Repositories\EloquentRepository;
use Modules\Extension\Models\CommentModel;

final class EloquentCommentRepository extends EloquentRepository implements CommentRepositoryInterface
{
    public function __construct(CommentModel $model)
    {
        parent::__construct($model);
    }
}
