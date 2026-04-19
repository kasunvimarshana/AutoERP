<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Entities\Currency;

interface FindCurrenciesServiceInterface
{
    public function find(int $id): ?Currency;

    public function findByCode(string $code): ?Currency;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator;
}
