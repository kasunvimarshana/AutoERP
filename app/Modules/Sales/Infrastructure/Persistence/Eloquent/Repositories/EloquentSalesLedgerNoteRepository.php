<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesLedgerNoteRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesLedgerNoteModel;

final class EloquentSalesLedgerNoteRepository extends EloquentRepository implements SalesLedgerNoteRepositoryInterface
{
    public function __construct(SalesLedgerNoteModel $model)
    {
        parent::__construct($model);
    }
}
