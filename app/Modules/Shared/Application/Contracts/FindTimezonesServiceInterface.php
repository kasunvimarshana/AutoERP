<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Shared\Domain\Entities\Timezone;

interface FindTimezonesServiceInterface
{
    public function find(int $id): ?Timezone;

    public function findByName(string $name): ?Timezone;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator;
}
