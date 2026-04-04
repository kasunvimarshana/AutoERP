<?php

namespace Modules\Returns\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Returns\Domain\Entities\ReturnAuthorization;

interface ReturnAuthorizationRepositoryInterface
{
    public function findById(int $id): ?ReturnAuthorization;

    public function findByRmaNumber(int $tenantId, string $rmaNumber): ?ReturnAuthorization;

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): ReturnAuthorization;

    public function update(ReturnAuthorization $rma, array $data): ReturnAuthorization;

    public function save(ReturnAuthorization $rma): ReturnAuthorization;
}
