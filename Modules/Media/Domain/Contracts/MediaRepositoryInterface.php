<?php

namespace Modules\Media\Domain\Contracts;

interface MediaRepositoryInterface
{
    /** Paginate media files for a tenant, optionally filtered by folder or polymorphic attachment. */
    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;

    /** Find a single media file by UUID. */
    public function findById(string $id): ?object;

    /** Persist a new media file record and return it. */
    public function create(array $data): object;

    /** Hard-delete a media file record. */
    public function delete(string $id): void;
}
