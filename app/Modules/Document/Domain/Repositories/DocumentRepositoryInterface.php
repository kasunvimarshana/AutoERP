<?php

namespace Modules\Document\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Document\Domain\Aggregates\DocumentAggregate;

interface DocumentRepositoryInterface
{
    public function save(DocumentAggregate $aggregate): DocumentAggregate;

    public function findById(int $id): ?DocumentAggregate;

    public function update(DocumentAggregate $aggregate): DocumentAggregate;

    public function delete(int $id): bool;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function storeAttachment(int $documentId, array $payload): array;
}
