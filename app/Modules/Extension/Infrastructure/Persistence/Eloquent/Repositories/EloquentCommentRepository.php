<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\CommentModel;

final class EloquentCommentRepository extends EloquentRepository implements CommentRepositoryInterface
{
    public function __construct(CommentModel $model)
    {
        parent::__construct($model);
    }
}