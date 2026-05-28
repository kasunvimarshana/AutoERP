<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseDocumentLinkRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseDocumentLinkModel;

final class EloquentPurchaseDocumentLinkRepository extends EloquentRepository implements PurchaseDocumentLinkRepositoryInterface
{
    public function __construct(PurchaseDocumentLinkModel $model)
    {
        parent::__construct($model);
    }
}
