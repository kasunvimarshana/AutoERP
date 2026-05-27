<?php

namespace Modules\Document\Application\Queries;

use Modules\Document\Domain\Aggregates\DocumentAggregate;
use Modules\Document\Domain\Exceptions\DocumentNotFoundException;
use Modules\Document\Domain\Repositories\DocumentRepositoryInterface;

class GetDocumentQuery
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository
    ) {
    }

    public function execute(int $tenantId, int $documentId): DocumentAggregate
    {
        $aggregate = $this->repository->findById($tenantId, $documentId);

        if ($aggregate === null) {
            throw new DocumentNotFoundException("Document [{$documentId}] was not found.");
        }

        return $aggregate;
    }
}
