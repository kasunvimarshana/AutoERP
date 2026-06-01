<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\PurchaseLedgerNoteRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseLedgerNoteModel;

final class EloquentPurchaseLedgerNoteRepository extends EloquentRepository implements PurchaseLedgerNoteRepositoryInterface
{
    public function __construct(PurchaseLedgerNoteModel $model)
    {
        parent::__construct($model);
    }
}
