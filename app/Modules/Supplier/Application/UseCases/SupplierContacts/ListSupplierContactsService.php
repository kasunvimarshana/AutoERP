<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\ListSupplierContactsServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierDefaults;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class ListSupplierContactsService implements ListSupplierContactsServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : SupplierDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('supplier.pagination.max_per_page', SupplierDefaults::MAX_PER_PAGE))
                : (int) config('supplier.pagination.default_per_page', SupplierDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}