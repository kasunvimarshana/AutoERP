<?php

declare(strict_types=1);

namespace Modules\Core\Application\Actions;

use Modules\Core\Application\DTOs\ListResourcesDto;
use Modules\Core\Domain\Contracts\Repositories\CrudRepositoryInterface;
use Modules\Core\Domain\Services\ResourceDomainService;

final class ListResourcesAction
{
    /**
     * @param  list<string>  $allowedFilterFields
     * @param  list<string>  $allowedSortColumns
     * @param  list<string>  $allowedIncludes
     */
    public function __construct(
        private readonly CrudRepositoryInterface $repository,
        private readonly ResourceDomainService $domainService,
        private readonly array $allowedFilterFields = [],
        private readonly array $allowedSortColumns = [],
        private readonly array $allowedIncludes = [],
    ) {}

    public function execute(ListResourcesDto $dto): mixed
    {
        $filters = $this->domainService->sanitizeFilters($dto->filters, $this->allowedFilterFields);
        $sort = $this->domainService->sanitizeSort($dto->sort, $this->allowedSortColumns);
        $include = $this->domainService->sanitizeInclude($dto->include, $this->allowedIncludes);

        return $this->repository->paginate(
            filters: $filters,
            perPage: $dto->perPage,
            page: $dto->page,
            sort: $sort,
            include: $include,
        );
    }
}
