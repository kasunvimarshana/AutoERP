<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Domain\Entities\FiscalPeriod;

interface FindFiscalPeriodServiceInterface
{
    public function find(mixed $id): ?FiscalPeriod;

    /**
     * @param array<string, mixed> $filters
     */
    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null): LengthAwarePaginator;
}
