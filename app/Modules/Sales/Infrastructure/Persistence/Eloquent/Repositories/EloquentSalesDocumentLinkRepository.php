<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesDocumentLinkRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesDocumentLinkModel;

final class EloquentSalesDocumentLinkRepository extends EloquentRepository implements SalesDocumentLinkRepositoryInterface
{
    public function __construct(SalesDocumentLinkModel $model)
    {
        parent::__construct($model);
    }
}
