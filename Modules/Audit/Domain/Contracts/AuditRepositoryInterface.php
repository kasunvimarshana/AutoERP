<?php

namespace Modules\Audit\Domain\Contracts;

interface AuditRepositoryInterface
{
    /**
     * Return a paginated list of audit logs filtered by the given criteria.
     *
     * @param  array{
     *     tenant_id?: string,
     *     model_type?: string,
     *     model_id?: string,
     *     action?: string,
     *     user_id?: string,
     * } $filters
     */
    public function paginate(array $filters, int $perPage = 50): object;

    /** Find a single audit log entry by its UUID. */
    public function findById(string $id): ?object;
}
