<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Entities\Country;

interface FindCountriesServiceInterface
{
    public function find(int $id): ?Country;

    public function findByCode(string $code): ?Country;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator;
}
